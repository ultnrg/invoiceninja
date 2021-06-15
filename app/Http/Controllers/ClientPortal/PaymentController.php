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

namespace App\Http\Controllers\ClientPortal;

use App\Exceptions\PaymentFailed;
use App\Factory\PaymentFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortal\Payments\PaymentResponseRequest;
use App\Jobs\Invoice\InjectSignature;
use App\Jobs\Util\SystemLogger;
use App\Models\CompanyGateway;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentHash;
use App\Models\SystemLog;
use App\Services\Subscription\SubscriptionService;
use App\Utils\Number;
use App\Utils\Traits\MakesDates;
use App\Utils\Traits\MakesHash;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Class PaymentController.
 */
class PaymentController extends Controller
{
    use MakesHash;
    use MakesDates;

    /**
     * Show the list of payments.
     *
     * @return Factory|View
     */
    public function index()
    {
        return $this->render('payments.index');
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Payment $payment
     * @return Factory|View
     */
    public function show(Request $request, Payment $payment)
    {
        $payment->load('invoices');

        return $this->render('payments.show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Presents the payment screen for a given
     * gateway and payment method.
     * The request will also contain the amount
     * and invoice ids for reference.
     *
     * @param Request $request
     * @return RedirectResponse|mixed
     */
    public function process(Request $request)
    {
        $is_credit_payment = false;
        $tokens = [];

        if ($request->input('company_gateway_id') == CompanyGateway::GATEWAY_CREDIT) {
            $is_credit_payment = true;
        }

        $gateway = CompanyGateway::find($request->input('company_gateway_id'));

        /**
         * find invoices
         *
         * ['invoice_id' => xxx, 'amount' => 22.00]
         */

        $payable_invoices = collect($request->payable_invoices);
        $invoices = Invoice::whereIn('id', $this->transformKeys($payable_invoices->pluck('invoice_id')->toArray()))->get();

        $invoices->each(function($invoice){
            $invoice->service()->removeUnpaidGatewayFees()->save();
        });

        /* pop non payable invoice from the $payable_invoices array */

        $payable_invoices = $payable_invoices->filter(function ($payable_invoice) use ($invoices) {
            return $invoices->where('hashed_id', $payable_invoice['invoice_id'])->first()->isPayable();
        });

        /*return early if no invoices*/

        if ($payable_invoices->count() == 0) {
            return redirect()
                ->route('client.invoices.index')
                ->with(['message' => 'No payable invoices selected.']);
        }

        $settings = auth()->user()->client->getMergedSettings();

        // nlog($settings);

        /* This loop checks for under / over payments and returns the user if a check fails */

        foreach ($payable_invoices as $payable_invoice) {

            /*Match the payable invoice to the Model Invoice*/

            $invoice = $invoices->first(function ($inv) use ($payable_invoice) {
                return $payable_invoice['invoice_id'] == $inv->hashed_id;
            });

            /*
             * Check if company supports over & under payments.
             * Determine the payable amount and the max payable. ie either partial or invoice balance
             */

            $payable_amount = Number::roundValue(Number::parseFloat($payable_invoice['amount']), auth()->user()->client->currency()->precision);
            $invoice_balance = Number::roundValue(($invoice->partial > 0 ? $invoice->partial : $invoice->balance), auth()->user()->client->currency()->precision);

            /*If we don't allow under/over payments force the payable amount - prevents inspect element adjustments in JS*/

            if ($settings->client_portal_allow_under_payment == false && $settings->client_portal_allow_over_payment == false) {
                $payable_invoice['amount'] = Number::roundValue(($invoice->partial > 0 ? $invoice->partial : $invoice->balance), auth()->user()->client->currency()->precision);
            }

            if (!$settings->client_portal_allow_under_payment && $payable_amount < $invoice_balance) {
                return redirect()
                    ->route('client.invoices.index')
                    ->with('message', ctrans('texts.minimum_required_payment', ['amount' => $invoice_balance]));
            }

            if ($settings->client_portal_allow_under_payment) {
                if ($invoice_balance < $settings->client_portal_under_payment_minimum && $payable_amount < $invoice_balance) {
                    return redirect()
                        ->route('client.invoices.index')
                        ->with('message', ctrans('texts.minimum_required_payment', ['amount' => $invoice_balance]));
                }

                if ($invoice_balance < $settings->client_portal_under_payment_minimum) {
                    // Skip the under payment rule.
                }

                if ($invoice_balance >= $settings->client_portal_under_payment_minimum && $payable_amount < $settings->client_portal_under_payment_minimum) {
                    return redirect()
                        ->route('client.invoices.index')
                        ->with('message', ctrans('texts.minimum_required_payment', ['amount' => $settings->client_portal_under_payment_minimum]));
                }
            }

            /* If we don't allow over payments and the amount exceeds the balance */

            if (!$settings->client_portal_allow_over_payment && $payable_amount > $invoice_balance) {
                return redirect()
                    ->route('client.invoices.index')
                    ->with('message', ctrans('texts.over_payments_disabled'));
            }

        }

        /*Iterate through invoices and add gateway fees and other payment metadata*/

        //$payable_invoices = $payable_invoices->map(function ($payable_invoice) use ($invoices, $settings) {
        $payable_invoice_collection = collect();

        foreach ($payable_invoices as $payable_invoice) {
            // nlog($payable_invoice);

            $payable_invoice['amount'] = Number::parseFloat($payable_invoice['amount']);

            $invoice = $invoices->first(function ($inv) use ($payable_invoice) {
                return $payable_invoice['invoice_id'] == $inv->hashed_id;
            });

            $payable_amount = Number::roundValue(Number::parseFloat($payable_invoice['amount']), auth()->user()->client->currency()->precision);
            $invoice_balance = Number::roundValue($invoice->balance, auth()->user()->client->currency()->precision);

            $payable_invoice['due_date'] = $this->formatDate($invoice->due_date, $invoice->client->date_format());
            $payable_invoice['invoice_number'] = $invoice->number;

            if (isset($invoice->po_number)) {
                $additional_info = $invoice->po_number;
            } elseif (isset($invoice->public_notes)) {
                $additional_info = $invoice->public_notes;
            } else {
                $additional_info = $invoice->date;
            }

            $payable_invoice['additional_info'] = $additional_info;

            $payable_invoice_collection->push($payable_invoice);
        }
        //});

        if (request()->has('signature') && !is_null(request()->signature) && !empty(request()->signature)) {
            $invoices->each(function ($invoice) use ($request) {
                InjectSignature::dispatch($invoice, $request->signature);
            });
        }

        $payable_invoices = $payable_invoice_collection;

        $payment_method_id = $request->input('payment_method_id');
        $invoice_totals = $payable_invoices->sum('amount');
        $first_invoice = $invoices->first();
        $credit_totals = $first_invoice->client->getSetting('use_credits_payment') == 'always' ? $first_invoice->client->service()->getCreditBalance() : 0;
        $starting_invoice_amount = $first_invoice->amount;

        if ($gateway) {
            $first_invoice->service()->addGatewayFee($gateway, $payment_method_id, $invoice_totals)->save();
        }

        /**
         * Gateway fee is calculated
         * by adding it as a line item, and then subtract
         * the starting and finishing amounts of the invoice.
         */
        $fee_totals = $first_invoice->amount - $starting_invoice_amount;

        if ($gateway) {
            $tokens = auth()->user()->client->gateway_tokens()
                ->whereCompanyGatewayId($gateway->id)
                ->whereGatewayTypeId($payment_method_id)
                ->get();
        }

        $hash_data = ['invoices' => $payable_invoices->toArray(), 'credits' => $credit_totals, 'amount_with_fee' => max(0, (($invoice_totals + $fee_totals) - $credit_totals))];

        if ($request->query('hash')) {
            $hash_data['billing_context'] = Cache::get($request->query('hash'));
        }

        $payment_hash = new PaymentHash;
        $payment_hash->hash = Str::random(32);
        $payment_hash->data = $hash_data;
        $payment_hash->fee_total = $fee_totals;
        $payment_hash->fee_invoice_id = $first_invoice->id;

        $payment_hash->save();

        $totals = [
            'credit_totals' => $credit_totals,
            'invoice_totals' => $invoice_totals,
            'fee_total' => $fee_totals,
            'amount_with_fee' => max(0, (($invoice_totals + $fee_totals) - $credit_totals)),
        ];

        $data = [
            'payment_hash' => $payment_hash->hash,
            'total' => $totals,
            'invoices' => $payable_invoices,
            'tokens' => $tokens,
            'payment_method_id' => $payment_method_id,
            'amount_with_fee' => $invoice_totals + $fee_totals,
        ];

        if ($is_credit_payment) {
            return $this->processCreditPayment($request, $data);
        }

        try {
            return $gateway
                ->driver(auth()->user()->client)
                ->setPaymentMethod($payment_method_id)
                ->setPaymentHash($payment_hash)
                ->checkRequirements()
                ->processPaymentView($data);
        } catch (\Exception $e) {
            SystemLogger::dispatch(
                $e->getMessage(),
                SystemLog::CATEGORY_GATEWAY_RESPONSE,
                SystemLog::EVENT_GATEWAY_ERROR,
                SystemLog::TYPE_FAILURE,
                auth('contact')->user()->client,
                auth('contact')->user()->client->company
            );

            throw new PaymentFailed($e->getMessage());
        }
    }

    public function response(PaymentResponseRequest $request)
    {
        $gateway = CompanyGateway::findOrFail($request->input('company_gateway_id'));

        $payment_hash = PaymentHash::whereRaw('BINARY `hash`= ?', [$request->payment_hash])->first();

            return $gateway
                ->driver(auth()->user()->client)
                ->setPaymentMethod($request->input('payment_method_id'))
                ->setPaymentHash($payment_hash)
                ->checkRequirements()
                ->processPaymentResponse($request);
    }

    /**
     * Pay for invoice/s using credits only.
     *
     * @param Request $request The request object
     * @return Response         The response view
     */
    public function credit_response(Request $request)
    {
        $payment_hash = PaymentHash::whereRaw('BINARY `hash`= ?', [$request->input('payment_hash')])->first();

        /* Hydrate the $payment */
        if ($payment_hash->payment()->exists()) {
            $payment = $payment_hash->payment;
        } else {
            $payment = PaymentFactory::create($payment_hash->fee_invoice->company_id, $payment_hash->fee_invoice->user_id);
            $payment->client_id = $payment_hash->fee_invoice->client_id;
            $payment->save();

            $payment_hash->payment_id = $payment->id;
            $payment_hash->save();
        }

        $payment = $payment->service()->applyCredits($payment_hash)->save();

        if (property_exists($payment_hash->data, 'billing_context')) {
            $billing_subscription = \App\Models\Subscription::find($payment_hash->data->billing_context->subscription_id);

            return (new SubscriptionService($billing_subscription))->completePurchase($payment_hash);
        }

        return redirect()->route('client.payments.show', ['payment' => $this->encodePrimaryKey($payment->id)]);
    }

    public function processCreditPayment(Request $request, array $data)
    {
        return render('gateways.credit.index', $data);
    }
}
