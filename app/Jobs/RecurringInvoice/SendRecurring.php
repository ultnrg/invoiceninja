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

namespace App\Jobs\RecurringInvoice;

use App\DataMapper\Analytics\SendRecurringFailure;
use App\Events\Invoice\InvoiceWasEmailed;
use App\Factory\RecurringInvoiceToInvoiceFactory;
use App\Jobs\Entity\EmailEntity;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Utils\Ninja;
use App\Utils\Traits\GeneratesCounter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Turbo124\Beacon\Facades\LightLogs;

class SendRecurring implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GeneratesCounter;

    public $recurring_invoice;

    protected $db;

    public $tries = 1;
    
    /**
     * Create a new job instance.
     *
     * @param RecurringInvoice $recurring_invoice
     * @param string $db
     */
    public function __construct(RecurringInvoice $recurring_invoice, string $db = 'db-ninja-01')
    {
        $this->recurring_invoice = $recurring_invoice;
        $this->db = $db;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() : void
    {
        // Generate Standard Invoice
        $invoice = RecurringInvoiceToInvoiceFactory::create($this->recurring_invoice, $this->recurring_invoice->client);

        $invoice->date = now()->format('Y-m-d');
        
        $invoice = $invoice->service()
                           ->markSent()
                           ->applyNumber()
                           ->createInvitations()
                           ->fillDefaults()
                           ->save();

        nlog("updating recurring invoice dates");
        /* Set next date here to prevent a recurring loop forming */
        $this->recurring_invoice->next_send_date = $this->recurring_invoice->nextSendDate();
        $this->recurring_invoice->remaining_cycles = $this->recurring_invoice->remainingCycles();
        $this->recurring_invoice->last_sent_date = now();

        /* Set completed if we don't have any more cycles remaining*/
        if ($this->recurring_invoice->remaining_cycles == 0) {
            $this->recurring_invoice->setCompleted();
        }

        nlog("next send date = " . $this->recurring_invoice->next_send_date);
        nlog("remaining cycles = " . $this->recurring_invoice->remaining_cycles);
        nlog("last send date = " . $this->recurring_invoice->last_sent_date);

        $this->recurring_invoice->save();
        
        //Admin notification for recurring invoice sent. 
        if ($invoice->invitations->count() >= 1) {
            $invoice->entityEmailEvent($invoice->invitations->first(), 'invoice', 'email_template_invoice');
        }
    
        nlog("Invoice {$invoice->number} created");

        $invoice->invitations->each(function ($invitation) use ($invoice) {
            if ($invitation->contact && strlen($invitation->contact->email) >=1) {

                try{
                    EmailEntity::dispatch($invitation, $invoice->company);
                }
                catch(\Exception $e) {
                    nlog($e->getMessage());
                }

                nlog("Firing email for invoice {$invoice->number}");
            }
        });
    
        if ($invoice->client->getSetting('auto_bill_date') == 'on_send_date' && $this->recurring_invoice->auto_bill_enabled) {
            nlog("attempting to autobill {$invoice->number}");
            $invoice->service()->autoBill()->save();
        }


    }

    public function failed($exception = null)
    {
        nlog('the job failed');

        $job_failure = new SendRecurringFailure();
        $job_failure->string_metric5 = get_class($this);
        $job_failure->string_metric6 = $exception->getMessage();

        LightLogs::create($job_failure)
                 ->batch();

        nlog(print_r($exception->getMessage(), 1));
    }
}
