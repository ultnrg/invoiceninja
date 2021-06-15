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

namespace App\PaymentDrivers\Authorize;

use App\Exceptions\PaymentFailed;
use App\Jobs\Mail\PaymentFailureMailer;
use App\Jobs\Util\SystemLogger;
use App\Models\ClientGatewayToken;
use App\Models\GatewayType;
use App\Models\Payment;
use App\Models\PaymentHash;
use App\Models\PaymentType;
use App\Models\SystemLog;
use App\PaymentDrivers\AuthorizePaymentDriver;
use App\Utils\Traits\MakesHash;

/**
 * Class AuthorizeCreditCard.
 */
class AuthorizeCreditCard
{
    use MakesHash;

    public $authorize;

    public function __construct(AuthorizePaymentDriver $authorize)
    {
        $this->authorize = $authorize;
    }

    public function processPaymentView($data)
    {
        $tokens = ClientGatewayToken::where('client_id', $this->authorize->client->id)
                                    ->where('company_gateway_id', $this->authorize->company_gateway->id)
                                    ->where('gateway_type_id', GatewayType::CREDIT_CARD)
                                    ->get();

        $data['tokens'] = $tokens;
        $data['gateway'] = $this->authorize;
        $data['public_client_id'] = $this->authorize->init()->getPublicClientKey();
        $data['api_login_id'] = $this->authorize->company_gateway->getConfigField('apiLoginId');

        return render('gateways.authorize.credit_card.pay', $data);
    }

    public function processPaymentResponse($request)
    {
        if ($request->token) {
            return $this->processTokenPayment($request);
        }

        $data = $request->all();

        $authorise_create_customer = new AuthorizeCreateCustomer($this->authorize, $this->authorize->client);

        $gateway_customer_reference = $authorise_create_customer->create($data);

        $authorise_payment_method = new AuthorizePaymentMethod($this->authorize);

        $payment_profile = $authorise_payment_method->addPaymentMethodToClient($gateway_customer_reference, $data);
        $payment_profile_id = $payment_profile->getPaymentProfile()->getCustomerPaymentProfileId();

        if ($request->has('store_card') && $request->input('store_card') === true) {
            $authorise_payment_method->payment_method = GatewayType::CREDIT_CARD;
            $client_gateway_token = $authorise_payment_method->createClientGatewayToken($payment_profile, $gateway_customer_reference);
        }

        $data = (new ChargePaymentProfile($this->authorize))->chargeCustomerProfile($gateway_customer_reference, $payment_profile_id, $data['amount_with_fee']);

        return $this->handleResponse($data, $request);
    }

    private function processTokenPayment($request)
    {
        $client_gateway_token = ClientGatewayToken::query()
            ->where('id', $this->decodePrimaryKey($request->token))
            ->where('company_id', auth('contact')->user()->client->company->id)
            ->first();

        if (!$client_gateway_token) {
            throw new PaymentFailed(ctrans('texts.payment_token_not_found'), 401);
        }

        $data = (new ChargePaymentProfile($this->authorize))->chargeCustomerProfile($client_gateway_token->gateway_customer_reference, $client_gateway_token->token, $request->input('amount_with_fee'));

        return $this->handleResponse($data, $request);
    }

    public function tokenBilling($cgt, $payment_hash)
    {
        $amount = array_sum(array_column($payment_hash->invoices(), 'amount')) + $payment_hash->fee_total;

        $data = (new ChargePaymentProfile($this->authorize))->chargeCustomerProfile($cgt->gateway_customer_reference, $cgt->token, $amount);

        /*Refactor and push to BaseDriver*/
        if ($data['response'] != null && $data['response']->getMessages()->getResultCode() == 'Ok') {

            $response = $data['response'];

            $this->storePayment($payment_hash, $data);

            $vars = [
                'invoices' => $payment_hash->invoices(),
                'amount' => $amount,
            ];

            $logger_message = [
                'server_response' => $response->getTransactionResponse()->getTransId(),
                'data' => $this->formatGatewayResponse($data, $vars),
            ];

            SystemLogger::dispatch($logger_message, SystemLog::CATEGORY_GATEWAY_RESPONSE, SystemLog::EVENT_GATEWAY_SUCCESS, SystemLog::TYPE_AUTHORIZE, $this->authorize->client);

            return true;
        } else {

            $vars = [
                'invoices' => $payment_hash->invoices(),
                'amount' => $amount,
            ];

            $logger_message = [
                'server_response' => $response->getTransactionResponse()->getTransId(),
                'data' => $this->formatGatewayResponse($data, $vars),
            ];

            PaymentFailureMailer::dispatch($this->authorize->client, $response->getTransactionResponse()->getTransId(), $this->authorize->client->company, $amount);

            SystemLogger::dispatch($logger_message, SystemLog::CATEGORY_GATEWAY_RESPONSE, SystemLog::EVENT_GATEWAY_FAILURE, SystemLog::TYPE_AUTHORIZE, $this->authorize->client, $this->authorize->client->company);

            return false;
        }
    }


    private function handleResponse($data, $request)
    {
        $response = $data['response'];

        if ($response != null && $response->getMessages()->getResultCode() == 'Ok') {

            return $this->processSuccessfulResponse($data, $request);
        }

        return $this->processFailedResponse($data, $request);
    }

    private function storePayment($payment_hash, $data)
    {
        $amount = array_sum(array_column($payment_hash->invoices(), 'amount')) + $payment_hash->fee_total;

        $response = $data['response'];

        $payment_record = [];
        $payment_record['amount'] = $amount;
        $payment_record['payment_type'] = PaymentType::CREDIT_CARD_OTHER;
        $payment_record['gateway_type_id'] = GatewayType::CREDIT_CARD;
        $payment_record['transaction_reference'] = $response->getTransactionResponse()->getTransId();

        $payment = $this->authorize->createPayment($payment_record);

        return $payment;
    }

    private function processSuccessfulResponse($data, $request)
    {
        $payment_hash = PaymentHash::whereRaw('BINARY `hash`= ?', [$request->input('payment_hash')])->firstOrFail();
        $payment = $this->storePayment($payment_hash, $data);

        $vars = [
            'invoices' => $payment_hash->invoices(),
            'amount' => array_sum(array_column($payment_hash->invoices(), 'amount')) + $payment_hash->fee_total,
        ];

        $logger_message = [
            'server_response' => $data['response']->getTransactionResponse()->getTransId(),
            'data' => $this->formatGatewayResponse($data, $vars),
        ];

        SystemLogger::dispatch(
            $logger_message,
            SystemLog::CATEGORY_GATEWAY_RESPONSE,
            SystemLog::EVENT_GATEWAY_SUCCESS,
            SystemLog::TYPE_AUTHORIZE,
            $this->authorize->client,
            $this->authorize->client->company,
        );

        return redirect()->route('client.payments.show', ['payment' => $this->encodePrimaryKey($payment->id)]);
    }

    private function processFailedResponse($data, $request)
    {
        $response = $data['response'];

        PaymentFailureMailer::dispatch($this->authorize->client, $response->getTransactionResponse()->getTransId(), $this->authorize->client->company, $data['amount_with_fee']);

        throw new \Exception(ctrans('texts.error_title'));
    }

    private function formatGatewayResponse($data, $vars)
    {
        $response = $data['response'];

        $code = '';
        $description = '';

        if($response->getTransactionResponse()->getMessages() !== null){
            $code = $response->getTransactionResponse()->getMessages()[0]->getCode();
            $description = $response->getTransactionResponse()->getMessages()[0]->getDescription();
        }

        return [
            'transaction_reference' => $response->getTransactionResponse()->getTransId(),
            'amount' => $vars['amount'],
            'auth_code' => $response->getTransactionResponse()->getAuthCode(),
            'code' => $code,
            'description' => $description,
            'invoices' => $vars['invoices'],
        ];
    }
}
