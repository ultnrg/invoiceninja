<?php

/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\PaymentDrivers\Stripe;

use App\Exceptions\PaymentFailed;
use App\Http\Requests\ClientPortal\Payments\PaymentResponseRequest;
use App\Jobs\Mail\PaymentFailureMailer;
use App\Jobs\Util\SystemLogger;
use App\Models\GatewayType;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\SystemLog;
use App\PaymentDrivers\StripePaymentDriver;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;

class CreditCard
{
    public $stripe;

    public function __construct(StripePaymentDriver $stripe)
    {
        $this->stripe = $stripe;
    }

    public function authorizeView(array $data)
    {
        $intent['intent'] = $this->stripe->getSetupIntent();

        return render('gateways.stripe.credit_card.authorize', array_merge($data, $intent));
    }

    public function authorizeResponse($request)
    {
        $this->stripe->init();

        $stripe_response = json_decode($request->input('gateway_response'));

        $customer = $this->stripe->findOrCreateCustomer();

        $this->stripe->attach($stripe_response->payment_method, $customer);

        $stripe_method = $this->stripe->getStripePaymentMethod($stripe_response->payment_method);

        $this->storePaymentMethod($stripe_method, $request->payment_method_id, $customer);

        return redirect()->route('client.payment_methods.index');
    }

    public function paymentView(array $data)
    {
        $payment_intent_data = [
            'amount' => $this->stripe->convertToStripeAmount($data['total']['amount_with_fee'], $this->stripe->client->currency()->precision),
            'currency' => $this->stripe->client->getCurrencyCode(),
            'customer' => $this->stripe->findOrCreateCustomer(),
            'description' => ctrans('texts.invoices') . ': ' . collect($data['invoices'])->pluck('invoice_number'), // TODO: More meaningful description.
        ];

        $payment_intent_data['setup_future_usage'] = 'off_session';

        $data['intent'] = $this->stripe->createPaymentIntent($payment_intent_data);
        $data['gateway'] = $this->stripe;

        return render('gateways.stripe.credit_card.pay', $data);
    }

    public function paymentResponse(PaymentResponseRequest $request)
    {
        $this->stripe->init();

        $state = [
            'server_response' => json_decode($request->gateway_response),
            'payment_hash' => $request->payment_hash,
        ];

        $state = array_merge($state, $request->all());
        $state['store_card'] = boolval($state['store_card']);

        if ($request->has('token') && !is_null($request->token)) {
            $state['store_card'] = false;
        }

        $state['payment_intent'] = PaymentIntent::retrieve($state['server_response']->id, $this->stripe->stripe_connect_auth);
        $state['customer'] = $state['payment_intent']->customer;

        $this->stripe->payment_hash->data = array_merge((array) $this->stripe->payment_hash->data, $state);
        $this->stripe->payment_hash->save();

        $server_response = $this->stripe->payment_hash->data->server_response;

        if ($server_response->status == 'succeeded') {

            $this->stripe->logSuccessfulGatewayResponse(['response' => json_decode($request->gateway_response), 'data' => $this->stripe->payment_hash], SystemLog::TYPE_STRIPE);

            return $this->processSuccessfulPayment();
        }

        return $this->processUnsuccessfulPayment($server_response);
    }

    private function processSuccessfulPayment()
    {
        $stripe_method = $this->stripe->getStripePaymentMethod($this->stripe->payment_hash->data->server_response->payment_method);

        $data = [
            'payment_method' => $this->stripe->payment_hash->data->server_response->payment_method,
            'payment_type' => PaymentType::parseCardType(strtolower($stripe_method->card->brand)),
            'amount' => $this->stripe->convertFromStripeAmount($this->stripe->payment_hash->data->server_response->amount, $this->stripe->client->currency()->precision),
            'transaction_reference' => optional($this->stripe->payment_hash->data->payment_intent->charges->data[0])->id,
            'gateway_type_id' => GatewayType::CREDIT_CARD,
        ];

        $this->stripe->payment_hash->data = array_merge((array) $this->stripe->payment_hash->data, ['amount' => $data['amount']]);
        $this->stripe->payment_hash->save();

        if ($this->stripe->payment_hash->data->store_card) {
            $customer = new \stdClass;
            $customer->id = $this->stripe->payment_hash->data->customer;

            $this->stripe->attach($this->stripe->payment_hash->data->server_response->payment_method, $customer);

            $stripe_method = $this->stripe->getStripePaymentMethod($this->stripe->payment_hash->data->server_response->payment_method);

            $this->storePaymentMethod($stripe_method, $this->stripe->payment_hash->data->payment_method_id, $customer);
        }

        $payment = $this->stripe->createPayment($data, Payment::STATUS_COMPLETED);

        SystemLogger::dispatch(
            ['response' => $this->stripe->payment_hash->data->server_response, 'data' => $data],
            SystemLog::CATEGORY_GATEWAY_RESPONSE,
            SystemLog::EVENT_GATEWAY_SUCCESS,
            SystemLog::TYPE_STRIPE,
            $this->stripe->client,
            $this->stripe->client->company,
        );

        return redirect()->route('client.payments.show', ['payment' => $this->stripe->encodePrimaryKey($payment->id)]);
    }

    private function processUnsuccessfulPayment($server_response)
    {
        PaymentFailureMailer::dispatch($this->stripe->client, $server_response->cancellation_reason, $this->stripe->client->company, $server_response->amount);

        PaymentFailureMailer::dispatch(
            $this->stripe->client,
            $server_response,
            $this->stripe->client->company,
            $server_response->amount
        );

        $message = [
            'server_response' => $server_response,
            'data' => $this->stripe->payment_hash->data,
        ];

        SystemLogger::dispatch(
            $message,
            SystemLog::CATEGORY_GATEWAY_RESPONSE,
            SystemLog::EVENT_GATEWAY_FAILURE,
            SystemLog::TYPE_STRIPE,
            $this->stripe->client,
            $this->stripe->client->company,
        );

        throw new PaymentFailed('Failed to process the payment.', 500);
    }

    private function storePaymentMethod(PaymentMethod $method, $payment_method_id, $customer)
    {
        try {
            $payment_meta = new \stdClass;
            $payment_meta->exp_month = (string) $method->card->exp_month;
            $payment_meta->exp_year = (string) $method->card->exp_year;
            $payment_meta->brand = (string) $method->card->brand;
            $payment_meta->last4 = (string) $method->card->last4;
            $payment_meta->type = GatewayType::CREDIT_CARD;

            $data = [
                'payment_meta' => $payment_meta,
                'token' => $method->id,
                'payment_method_id' => $payment_method_id,
            ];

            $this->stripe->storeGatewayToken($data, ['gateway_customer_reference' => $customer->id]);
        } catch (\Exception $e) {
            return $this->stripe->processInternallyFailedPayment($this->stripe, $e);
        }
    }
}
