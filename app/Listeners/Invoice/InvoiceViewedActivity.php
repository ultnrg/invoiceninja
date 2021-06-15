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

use App\Libraries\MultiDB;
use App\Models\Activity;
use App\Repositories\ActivityRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use stdClass;

class InvoiceViewedActivity implements ShouldQueue
{
    protected $activity_repo;

    /**
     * Create the event listener.
     *
     * @param ActivityRepository $activity_repo
     */
    public function __construct(ActivityRepository $activity_repo)
    {
        $this->activity_repo = $activity_repo;
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

        $fields = new stdClass;

        $fields->user_id = $event->invitation->user_id;
        $fields->company_id = $event->invitation->company_id;
        $fields->activity_type_id = Activity::VIEW_INVOICE;
        $fields->client_id = $event->invitation->invoice->client_id;
        $fields->client_contact_id = $event->invitation->client_contact_id;
        $fields->invitation_id = $event->invitation->id;
        $fields->invoice_id = $event->invitation->invoice_id;

        $this->activity_repo->save($fields, $event->invitation->invoice, $event->event_vars);
    }
}
