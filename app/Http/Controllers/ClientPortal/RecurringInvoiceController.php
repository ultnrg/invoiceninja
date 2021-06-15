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

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortal\ShowRecurringInvoiceRequest;
use App\Jobs\Mail\NinjaMailer;
use App\Jobs\Mail\NinjaMailerJob;
use App\Jobs\Mail\NinjaMailerObject;
use App\Mail\RecurringInvoice\ClientContactRequestCancellationObject;
use App\Models\RecurringInvoice;
use App\Notifications\ClientContactRequestCancellation;
use App\Utils\Traits\MakesDates;
use App\Utils\Traits\MakesHash;
use App\Utils\Traits\Notifications\UserNotifies;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Class InvoiceController.
 */
class RecurringInvoiceController extends Controller
{
    use MakesHash;
    use MakesDates;
    use UserNotifies;

    /**
     * Show the list of recurring invoices.
     *
     * @return Factory|View
     */
    public function index()
    {
        return $this->render('recurring_invoices.index');
    }

    /**
     * Display the recurring invoice.
     *
     * @param ShowRecurringInvoiceRequest $request
     * @param RecurringInvoice $recurring_invoice
     * @return Factory|View
     */
    public function show(ShowRecurringInvoiceRequest $request, RecurringInvoice $recurring_invoice)
    {
        return $this->render('recurring_invoices.show', [
            'invoice' => $recurring_invoice->load('invoices'),
        ]);
    }

    public function requestCancellation(Request $request, RecurringInvoice $recurring_invoice)
    {
        if (is_null($recurring_invoice->subscription_id) || optional($recurring_invoice->subscription)->allow_cancellation) {
            $nmo = new NinjaMailerObject;
            $nmo->mailable = (new NinjaMailer((new ClientContactRequestCancellationObject($recurring_invoice, auth()->user()))->build()));
            $nmo->company = $recurring_invoice->company;
            $nmo->settings = $recurring_invoice->company->settings;

            $notifiable_users = $this->filterUsersByPermissions($recurring_invoice->company->company_users, $recurring_invoice, ['recurring_cancellation']);

            $notifiable_users->each(function ($company_user) use($nmo){

                $nmo->to_user = $company_user->user;
                NinjaMailerJob::dispatch($nmo);

            });

            //$recurring_invoice->user->notify(new ClientContactRequestCancellation($recurring_invoice, auth()->user()));

            return $this->render('recurring_invoices.cancellation.index', [
                'invoice' => $recurring_invoice,
            ]);
        }

        return back();
    }
}
