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

namespace App\Listeners\Invoice;

use App\Jobs\Entity\CreateEntityPdf;
use App\Libraries\MultiDB;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateInvoicePdf implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        MultiDB::setDb($event->company->db);

        if(isset($event->invoice))
        {
            $event->invoice->invitations->each(function ($invitation) {
                CreateEntityPdf::dispatch($invitation);
            });
        }

        if(isset($event->quote))
        {
            $event->quote->invitations->each(function ($invitation) {
                CreateEntityPdf::dispatch($invitation);
            });
        }

        if(isset($event->credit))
        {
            $event->credit->invitations->each(function ($invitation) {
                CreateEntityPdf::dispatch($invitation);
            });
        }

    }
}
