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

namespace App\Services\Subscription;

use App\DataMapper\InvoiceItem;
use App\Factory\CreditFactory;
use App\Factory\InvoiceFactory;
use App\Factory\InvoiceToRecurringInvoiceFactory;
use App\Factory\RecurringInvoiceFactory;
use App\Jobs\Util\SubscriptionWebhookHandler;
use App\Jobs\Util\SystemLogger;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Invoice;
use App\Models\PaymentHash;
use App\Models\Product;
use App\Models\RecurringInvoice;
use App\Models\Subscription;
use App\Models\SystemLog;
use App\Repositories\CreditRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\RecurringInvoiceRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\Subscription\ZeroCostProduct;
use App\Utils\Ninja;
use App\Utils\Traits\CleanLineItems;
use App\Utils\Traits\MakesHash;
use App\Utils\Traits\SubscriptionHooker;
use Carbon\Carbon;
use GuzzleHttp\RequestOptions;

class SubscriptionService
{
    use MakesHash;
    use CleanLineItems;
    use SubscriptionHooker;

    /** @var subscription */
    private $subscription;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /*
        Performs the initial purchase of a
        one time or recurring product
    */
    public function completePurchase(PaymentHash $payment_hash)
    {

        if (!property_exists($payment_hash->data, 'billing_context')) {
            throw new \Exception("Illegal entrypoint into method, payload must contain billing context");
        }

        if($payment_hash->data->billing_context->context == 'change_plan') {
            return $this->handlePlanChange($payment_hash);
        }

        // if we have a recurring product - then generate a recurring invoice
        if(strlen($this->subscription->recurring_product_ids) >=1){

            $recurring_invoice = $this->convertInvoiceToRecurring($payment_hash->payment->client_id);
            $recurring_invoice_repo = new RecurringInvoiceRepository();

            $recurring_invoice->next_send_date = now();
            $recurring_invoice = $recurring_invoice_repo->save([], $recurring_invoice);
            $recurring_invoice->next_send_date = $recurring_invoice->nextSendDate();

            /* Start the recurring service */
            $recurring_invoice->service()
                              ->start()
                              ->save();

            //execute any webhooks

            $context = [
                'context' => 'recurring_purchase',
                'recurring_invoice' => $recurring_invoice->hashed_id,
                'invoice' => $this->encodePrimaryKey($payment_hash->fee_invoice_id),
                'client' => $recurring_invoice->client->hashed_id,
                'subscription' => $this->subscription->hashed_id,
                'contact' => auth('contact')->user()->hashed_id,
            ];

            $response = $this->triggerWebhook($context);

            // nlog($response);

            $this->handleRedirect('/client/recurring_invoices/'.$recurring_invoice->hashed_id);

        }
        else
        {
            $invoice = Invoice::find($payment_hash->fee_invoice_id);

            $context = [
                'context' => 'single_purchase',
                'invoice' => $this->encodePrimaryKey($payment_hash->fee_invoice_id),
                'client'  => $invoice->client->hashed_id,
                'subscription' => $this->subscription->hashed_id,
            ];

            //execute any webhooks
            $this->triggerWebhook($context);

            $this->handleRedirect('/client/invoices/'.$this->encodePrimaryKey($payment_hash->fee_invoice_id));

        }
    }

    /* Hits the client endpoint to determine whether the user is able to access this subscription */
    public function isEligible($contact)
    {
        $context = [
            'context' => 'is_eligible',
            'subscription' => $this->subscription->hashed_id,
            'contact' => $contact->hashed_id,
            'contact_email' => $contact->email,
            'client' => $contact->client->hashed_id,
        ];

        $response = $this->triggerWebhook($context);

        return $response;
    }

    /* Starts the process to create a trial
        - we create a recurring invoice, which is has its next_send_date as now() + trial_duration
        - we then hit the client API end point to advise the trial payload
        - we then return the user to either a predefined user endpoint, OR we return the user to the recurring invoice page.
    */
    public function startTrial(array $data)
    {
        // Redirects from here work just fine. Livewire will respect it.
        $client_contact = ClientContact::find($data['contact_id']);

        if(!$this->subscription->trial_enabled)
            return new \Exception("Trials are disabled for this product");

        //create recurring invoice with start date = trial_duration + 1 day
        $recurring_invoice_repo = new RecurringInvoiceRepository();

        $recurring_invoice = $this->convertInvoiceToRecurring($client_contact->client_id);
        $recurring_invoice->next_send_date = now()->addSeconds($this->subscription->trial_duration);
        $recurring_invoice->backup = 'is_trial';

        if(array_key_exists('coupon', $data) && ($data['coupon'] == $this->subscription->promo_code) && $this->subscription->promo_discount > 0)
        {
            $recurring_invoice->discount = $this->subscription->promo_discount;
            $recurring_invoice->is_amount_discount = $this->subscription->is_amount_discount;
        }

        $recurring_invoice = $recurring_invoice_repo->save($data, $recurring_invoice);

        /* Start the recurring service */
        $recurring_invoice->service()
                          ->start()
                          ->save();

            $context = [
                'context' => 'trial',
                'recurring_invoice' => $recurring_invoice->hashed_id,
                'client' => $recurring_invoice->client->hashed_id,
                'subscription' => $this->subscription->hashed_id,
            ];

        //execute any webhooks
        $response = $this->triggerWebhook($context);

        return $this->handleRedirect('/client/recurring_invoices/'.$recurring_invoice->hashed_id);
    }

    /**
     * Returns an upgrade price when moving between plans
     *
     * However we only allow people to  move between plans
     * if their account is in good standing.
     *
     * @param  RecurringInvoice $recurring_invoice
     * @param  Subscription     $target
     *
     * @return float
     */
    public function calculateUpgradePrice(RecurringInvoice $recurring_invoice, Subscription $target) :?float
    {
        //calculate based on daily prices

        $current_amount = $recurring_invoice->amount;
        $currency_frequency = $recurring_invoice->frequency_id;

        $outstanding = $recurring_invoice->invoices()
                                         ->where('is_deleted', 0)
                                         ->whereIn('status_id', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIAL])
                                         ->where('balance', '>', 0);

        $outstanding_amounts = $outstanding->sum('balance');

        $outstanding_invoice = Invoice::where('subscription_id', $this->subscription->id)
                                         ->where('client_id', $recurring_invoice->client_id)
                                         ->where('is_deleted', 0)
                                         ->orderBy('id', 'desc')
                                         ->first();

        if ($outstanding->count() == 0){
            //nothing outstanding
            return $target->price - $this->calculateProRataRefund($outstanding_invoice);
        }
        elseif ($outstanding->count() == 1){
            //user has multiple amounts outstanding
            return $target->price - $this->calculateProRataRefund($outstanding_invoice);
        }
        elseif ($outstanding->count() > 1) {
            //user is changing plan mid frequency cycle
            //we cannot handle this if there are more than one invoice outstanding.
            return null;
        }

        return null;

    }

    /**
     * We refund unused days left.
     *
     * @param  Invoice $invoice
     * @return float
     */
    private function calculateProRataRefund($invoice) :float
    {
        if(!$invoice->date)
            return 0;

        $start_date = Carbon::parse($invoice->date);

        $current_date = now();

        $days_of_subscription_used = $start_date->diffInDays($current_date);

        $days_in_frequency = $this->getDaysInFrequency();

        $pro_rata_refund = round((($days_in_frequency - $days_of_subscription_used)/$days_in_frequency) * $invoice->amount ,2);

        return $pro_rata_refund;

    }

    /**
     * Returns refundable set of line items
     * transformed for direct injection into
     * the invoice
     *
     * @param  Invoice $invoice
     * @return array
     */
    private function calculateProRataRefundItems($invoice, $is_credit = false) :array
    {
        /* depending on whether we are creating an invoice or a credit*/
        $multiplier = $is_credit ? 1 : -1;

        $start_date = Carbon::parse($invoice->date);

        $current_date = now();

        $days_of_subscription_used = $start_date->diffInDays($current_date);

        $days_in_frequency = $this->getDaysInFrequency();

        $ratio = ($days_in_frequency - $days_of_subscription_used)/$days_in_frequency;

        $line_items = [];

        foreach($invoice->line_items as $item)
        {

            if($item->product_key != ctrans('texts.refund'))
            {

                $item->cost = ($item->cost*$ratio*$multiplier);
                $item->product_key = ctrans('texts.refund');
                $item->notes = ctrans('texts.refund') . ": ". $item->notes;


                $line_items[] = $item;

            }
        }

        return $line_items;

    }

    /**
     * We only charge for the used days
     *
     * @param  Invoice $invoice
     * @return float
     */
    private function calculateProRataCharge($invoice) :float
    {

        $start_date = Carbon::parse($invoice->date);

        $current_date = now();

        $days_to_charge = $start_date->diffInDays($current_date);

        $days_in_frequency = $this->getDaysInFrequency();

        nlog("days to charge = {$days_to_charge} days in frequency = {$days_in_frequency}");

        $pro_rata_charge = round(($days_to_charge/$days_in_frequency) * $invoice->amount ,2);

        nlog("pro rata charge = {$pro_rata_charge}");

        return $pro_rata_charge;
    }

    /**
     * When downgrading, we may need to create
     * a credit
     *
     * @param  array $data
     */
    public function createChangePlanCredit($data)
    {
        $recurring_invoice = $data['recurring_invoice'];
        $old_subscription = $data['subscription'];
        $target_subscription = $data['target'];

        $pro_rata_charge_amount = 0;
        $pro_rata_refund_amount = 0;

        $last_invoice = Invoice::where('subscription_id', $recurring_invoice->subscription_id)
                                         ->where('client_id', $recurring_invoice->client_id)
                                         ->where('is_deleted', 0)
                                         ->withTrashed()
                                         ->orderBy('id', 'desc')
                                         ->first();

        if($last_invoice->balance > 0)
        {
            $pro_rata_charge_amount = $this->calculateProRataCharge($last_invoice, $old_subscription);
            nlog("pro rata charge = {$pro_rata_charge_amount}");
        }
        else
        {
            $pro_rata_refund_amount = $this->calculateProRataRefund($last_invoice, $old_subscription) * -1;
            nlog("pro rata refund = {$pro_rata_refund_amount}");
        }

        $total_payable = $pro_rata_refund_amount + $pro_rata_charge_amount + $this->subscription->price;

        nlog("total payable = {$total_payable}");

        $credit = $this->createCredit($last_invoice, $target_subscription);

        $new_recurring_invoice = $this->createNewRecurringInvoice($recurring_invoice);

            $context = [
                'context' => 'change_plan',
                'recurring_invoice' => $new_recurring_invoice->hashed_id,
                'credit' => $credit->hashed_id,
                'client' => $new_recurring_invoice->client->hashed_id,
                'subscription' => $target_subscription->hashed_id,
                'contact' => auth('contact')->user()->hashed_id,
            ];

            $response = $this->triggerWebhook($context);

            nlog($response);

            return $this->handleRedirect('/client/credits/'.$credit->hashed_id);

    }

    /**
     * When changing plans, we need to generate a pro rata invoice
     *
     * @param  array $data
     * @return Invoice
     */
    public function createChangePlanInvoice($data)
    {
        $recurring_invoice = $data['recurring_invoice'];
        $old_subscription = $data['subscription'];
        $target_subscription = $data['target'];

        $pro_rata_charge_amount = 0;
        $pro_rata_refund_amount = 0;

        $last_invoice = Invoice::where('subscription_id', $recurring_invoice->subscription_id)
                                         ->where('client_id', $recurring_invoice->client_id)
                                         ->where('is_deleted', 0)
                                         ->withTrashed()
                                         ->orderBy('id', 'desc')
                                         ->first();

        if($last_invoice->balance > 0)
        {
            $pro_rata_charge_amount = $this->calculateProRataCharge($last_invoice, $old_subscription);
            nlog("pro rata charge = {$pro_rata_charge_amount}");
        }
        else
        {
            $pro_rata_refund_amount = $this->calculateProRataRefund($last_invoice, $old_subscription) * -1;
            nlog("pro rata refund = {$pro_rata_refund_amount}");
        }

        $total_payable = $pro_rata_refund_amount + $pro_rata_charge_amount + $this->subscription->price;

        return $this->proRataInvoice($last_invoice, $target_subscription);

    }

    /**
     * Response from payment service on
     * return from a plan change
     *
     * @param  PaymentHash $payment_hash
     */
    private function handlePlanChange($payment_hash)
    {

        $old_recurring_invoice = RecurringInvoice::find($payment_hash->data->billing_context->recurring_invoice);

        $recurring_invoice = $this->createNewRecurringInvoice($old_recurring_invoice);

            $context = [
                'context' => 'change_plan',
                'recurring_invoice' => $recurring_invoice->hashed_id,
                'invoice' => $this->encodePrimaryKey($payment_hash->fee_invoice_id),
                'client' => $recurring_invoice->client->hashed_id,
                'subscription' => $this->subscription->hashed_id,
                'contact' => auth('contact')->user()->hashed_id,
            ];


            $response = $this->triggerWebhook($context);

            nlog($response);

            return $this->handleRedirect('/client/recurring_invoices/'.$recurring_invoice->hashed_id);

    }

    /**
     * Creates a new recurring invoice when changing
     * plans
     *
     * @param  RecurringInvoice $old_recurring_invoice
     * @return RecurringInvoice
     */
    private function createNewRecurringInvoice($old_recurring_invoice) :RecurringInvoice
    {

        $old_recurring_invoice->service()->stop()->save();

        $recurring_invoice_repo = new RecurringInvoiceRepository();
        $recurring_invoice_repo->archive($old_recurring_invoice);

            $recurring_invoice = $this->convertInvoiceToRecurring($old_recurring_invoice->client_id);
            $recurring_invoice = $recurring_invoice_repo->save([], $recurring_invoice);
            $recurring_invoice->next_send_date = now();
            $recurring_invoice->next_send_date = $recurring_invoice->nextSendDate();

            /* Start the recurring service */
            $recurring_invoice->service()
                              ->start()
                              ->save();

          return $recurring_invoice;

    }

    /**
     * Handle a plan change where no payment is required
     * 
     * @param  array $data
     * @deprecated - no usage found
     */
    public function handlePlanChangeNoPayment($data)
    {

        $recurring_invoice = $this->createNewRecurringInvoice($data['recurring_invoice']);

            $context = [
                'context' => 'change_plan',
                'recurring_invoice' => $recurring_invoice->hashed_id,
                'invoice' => $this->encodePrimaryKey($payment_hash->fee_invoice_id),
                'client' => $recurring_invoice->client->hashed_id,
                'subscription' => $this->subscription->hashed_id,
                'contact' => auth('contact')->user()->hashed_id,
            ];

            $response = $this->triggerWebhook($context);

            // nlog($response);

            return $this->handleRedirect('/client/recurring_invoices/'.$recurring_invoice->hashed_id);
    }

    /**
     * Creates a credit note if the plan change requires
     *
     * @param  Invoice $last_invoice
     * @param  Subscription $target
     * @return Credit
     */
    private function createCredit($last_invoice, $target)
    {

        $subscription_repo = new SubscriptionRepository();
        $credit_repo = new CreditRepository();

        $credit = CreditFactory::create($this->subscription->company_id, $this->subscription->user_id);
        $credit->date = now()->format('Y-m-d');
        $credit->subscription_id = $this->subscription->id;

        $line_items = $subscription_repo->generateLineItems($target);

        $credit->line_items = array_merge($line_items, $this->calculateProRataRefundItems($last_invoice, true));

        $data = [
            'client_id' => $last_invoice->client_id,
            'quantity' => 1,
            'date' => now()->format('Y-m-d'),
        ];

        return $credit_repo->save($data, $credit)->service()->markSent()->fillDefaults()->save();

    }

    /**
     * When changing plans we need to generate a pro rata
     * invoice which takes into account any credits.
     *
     * @param  Invoice $last_invoice
     * @param  Subscription $target
     * @return Invoice
     */
    private function proRataInvoice($last_invoice, $target)
    {
        $subscription_repo = new SubscriptionRepository();
        $invoice_repo = new InvoiceRepository();

        $invoice = InvoiceFactory::create($this->subscription->company_id, $this->subscription->user_id);
        $invoice->date = now()->format('Y-m-d');
        $invoice->subscription_id = $this->subscription->id;

        $invoice->line_items = array_merge($subscription_repo->generateLineItems($target), $this->calculateProRataRefundItems($last_invoice));

        $data = [
            'client_id' => $last_invoice->client_id,
            'quantity' => 1,
            'date' => now()->format('Y-m-d'),
        ];

        return $invoice_repo->save($data, $invoice)
                            ->service()
                            ->markSent()
                            ->fillDefaults()
                            ->save();

    }

    /**
     * Generates the first invoice when a subscription is purchased
     *
     * @param  array $data
     * @return Invoice
     */
    public function createInvoice($data): ?\App\Models\Invoice
    {

        $invoice_repo = new InvoiceRepository();
        $subscription_repo = new SubscriptionRepository();

        $invoice = InvoiceFactory::create($this->subscription->company_id, $this->subscription->user_id);
        $invoice->line_items = $subscription_repo->generateLineItems($this->subscription);
        $invoice->subscription_id = $this->subscription->id;

        if(strlen($data['coupon']) >=1 && ($data['coupon'] == $this->subscription->promo_code) && $this->subscription->promo_discount > 0)
        {
            $invoice->discount = $this->subscription->promo_discount;
            $invoice->is_amount_discount = $this->subscription->is_amount_discount;
        }

        return $invoice_repo->save($data, $invoice);

    }

    /**
     * Generates a recurring invoice based on
     * the specifications of the subscription
     *
     * @param  int $client_id The Client Id
     * @return RecurringInvoice
     */
    public function convertInvoiceToRecurring($client_id) :RecurringInvoice
    {

        $subscription_repo = new SubscriptionRepository();

        $recurring_invoice = RecurringInvoiceFactory::create($this->subscription->company_id, $this->subscription->user_id);
        $recurring_invoice->client_id = $client_id;
        $recurring_invoice->line_items = $subscription_repo->generateLineItems($this->subscription, true);
        $recurring_invoice->subscription_id = $this->subscription->id;
        $recurring_invoice->frequency_id = $this->subscription->frequency_id ?: RecurringInvoice::FREQUENCY_MONTHLY;
        $recurring_invoice->date = now();
        $recurring_invoice->remaining_cycles = -1;

        return $recurring_invoice;
    }

    /**
     * Hit a 3rd party API if defined in the subscription
     *
     * @param  array $context
     */
    public function triggerWebhook($context)
    {
        if (empty($this->subscription->webhook_configuration['post_purchase_url']) || is_null($this->subscription->webhook_configuration['post_purchase_url']) || strlen($this->subscription->webhook_configuration['post_purchase_url']) < 1) {
            return ["message" => "Success", "status_code" => 200];
        }

        $response = false;

        $body = array_merge($context, [
            'company_key' => $this->subscription->company->company_key,
            'account_key' => $this->subscription->company->account->key,
            'db' => $this->subscription->company->db,
        ]);

        $response = $this->sendLoad($this->subscription, $body);

        /* Append the response to the system logger body */
        if(is_array($response)){

            $body = $response;

        }
        else {

            $body = $response->getStatusCode();

        }

        $client = \App\Models\Client::find($this->decodePrimaryKey($body['client']));

            SystemLogger::dispatch(
                $body,
                SystemLog::CATEGORY_WEBHOOK,
                SystemLog::EVENT_WEBHOOK_RESPONSE,
                SystemLog::TYPE_WEBHOOK_RESPONSE,
                $client,
                $client->company,
            );

        if(is_array($body))
          return $response;
        else
          return ['message' => 'There was a problem encountered with the webhook', 'status_code' => 500];

    }

    public function fireNotifications()
    {
        //scan for any notification we are required to send
    }

    /**
     * Get the single charge products for the
     * subscription
     *
     * @return ?Product Collection
     */
    public function products()
    {
        return Product::whereIn('id', $this->transformKeys(explode(",", $this->subscription->product_ids)))->get();
    }

    /**
     * Get the recurring products for the
     * subscription
     *
     * @return ?Product Collection
     */
    public function recurring_products()
    {
        return Product::whereIn('id', $this->transformKeys(explode(",", $this->subscription->recurring_product_ids)))->get();
    }

    /**
     * Get available upgrades & downgrades for the plan.
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getPlans()
    {
        return Subscription::query()
            ->where('company_id', $this->subscription->company_id)
            ->where('group_id', $this->subscription->group_id)
            ->where('id', '!=', $this->subscription->id)
            ->get();
    }

    /**
     * Handle the cancellation of a subscription
     *
     * @param  RecurringInvoice $recurring_invoice
     *
     */
    public function handleCancellation(RecurringInvoice $recurring_invoice)
    {

        //only refund if they are in the refund window.
        $outstanding_invoice = Invoice::where('subscription_id', $this->subscription->id)
                                     ->where('client_id', $recurring_invoice->client_id)
                                     ->where('is_deleted', 0)
                                     ->orderBy('id', 'desc')
                                     ->first();

        $invoice_start_date = Carbon::parse($outstanding_invoice->date);
        $refund_end_date = $invoice_start_date->addSeconds($this->subscription->refund_period);

        /* Stop the recurring invoice and archive */
        $recurring_invoice->service()->stop()->save();
        $recurring_invoice_repo = new RecurringInvoiceRepository();
        $recurring_invoice_repo->archive($recurring_invoice);

        /* Refund only if we are in the window - and there is nothing outstanding on the invoice */
        if($refund_end_date->greaterThan(now()) && (int)$outstanding_invoice->balance == 0)
        {

            if($outstanding_invoice->payments()->exists())
            {
                $payment = $outstanding_invoice->payments()->first();

                $data = [
                    'id' => $payment->id,
                    'gateway_refund' => true,
                    'send_email' => true,
                    'invoices' => [
                        ['invoice_id' => $outstanding_invoice->id, 'amount' => $outstanding_invoice->amount],
                    ],

                ];

                $payment->refund($data);
            }
        }

            $context = [
                'context' => 'cancellation',
                'subscription' => $this->subscription->hashed_id,
                'recurring_invoice' => $recurring_invoice->hashed_id,
                'client' => $recurring_invoice->client->hashed_id,
                'contact' => auth('contact')->user()->hashed_id,
            ];

            $this->triggerWebhook($context);

            return $this->handleRedirect('client/subscriptions');

    }

    private function getDaysInFrequency()
    {

        switch ($this->subscription->frequency_id) {
            case RecurringInvoice::FREQUENCY_DAILY:
                return 1;
            case RecurringInvoice::FREQUENCY_WEEKLY:
                return 7;
            case RecurringInvoice::FREQUENCY_TWO_WEEKS:
                return 14;
            case RecurringInvoice::FREQUENCY_FOUR_WEEKS:
                return now()->diffInDays(now()->addWeeks(4));
            case RecurringInvoice::FREQUENCY_MONTHLY:
                return now()->diffInDays(now()->addMonthNoOverflow());
            case RecurringInvoice::FREQUENCY_TWO_MONTHS:
                return now()->diffInDays(now()->addMonthNoOverflow(2));
            case RecurringInvoice::FREQUENCY_THREE_MONTHS:
                return now()->diffInDays(now()->addMonthNoOverflow(3));
            case RecurringInvoice::FREQUENCY_FOUR_MONTHS:
                return now()->diffInDays(now()->addMonthNoOverflow(4));
            case RecurringInvoice::FREQUENCY_SIX_MONTHS:
                return now()->diffInDays(now()->addMonthNoOverflow(6));
            case RecurringInvoice::FREQUENCY_ANNUALLY:
                return now()->diffInDays(now()->addYear());
            case RecurringInvoice::FREQUENCY_TWO_YEARS:
                return now()->diffInDays(now()->addYears(2));
            case RecurringInvoice::FREQUENCY_THREE_YEARS:
                return now()->diffInDays(now()->addYears(3));
            default:
                return 0;
        }

    }


    /**
    * 'email' => $this->email ?? $this->contact->email,
    * 'quantity' => $this->quantity,
    * 'contact_id' => $this->contact->id,
    */
    public function handleNoPaymentRequired(array $data)
    {

        $context = (new ZeroCostProduct($this->subscription, $data))->run();

        // Forward payload to webhook
        if(array_key_exists('context', $context))
            $response = $this->triggerWebhook($context);

        // Hit the redirect
        return $this->handleRedirect($context['redirect_url']);

    }

    /**
     * Handles redirecting the user
     */
    private function handleRedirect($default_redirect)
    {

        if(array_key_exists('return_url', $this->subscription->webhook_configuration) && strlen($this->subscription->webhook_configuration['return_url']) >=1)
            return redirect($this->subscription->webhook_configuration['return_url']);

        return redirect($default_redirect);
    }
}
