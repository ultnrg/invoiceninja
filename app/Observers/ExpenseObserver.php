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

namespace App\Observers;

use App\Jobs\Util\WebhookHandler;
use App\Models\Expense;
use App\Models\Webhook;

class ExpenseObserver
{
    /**
     * Handle the expense "created" event.
     *
     * @param Expense $expense
     * @return void
     */
    public function created(Expense $expense)
    {
        $subscriptions = Webhook::where('company_id', $expense->company->id)
                            ->where('event_id', Webhook::EVENT_CREATE_EXPENSE)
                            ->exists();

        if ($subscriptions) {
            WebhookHandler::dispatch(Webhook::EVENT_CREATE_EXPENSE, $expense, $expense->company);
        }
    }

    /**
     * Handle the expense "updated" event.
     *
     * @param Expense $expense
     * @return void
     */
    public function updated(Expense $expense)
    {
        $subscriptions = Webhook::where('company_id', $expense->company->id)
                            ->where('event_id', Webhook::EVENT_UPDATE_EXPENSE)
                            ->exists();

        if ($subscriptions) {
            WebhookHandler::dispatch(Webhook::EVENT_UPDATE_EXPENSE, $expense, $expense->company);
        }
    }

    /**
     * Handle the expense "deleted" event.
     *
     * @param Expense $expense
     * @return void
     */
    public function deleted(Expense $expense)
    {
        $subscriptions = Webhook::where('company_id', $expense->company->id)
                            ->where('event_id', Webhook::EVENT_DELETE_EXPENSE)
                            ->exists();

        if ($subscriptions) {
            WebhookHandler::dispatch(Webhook::EVENT_DELETE_EXPENSE, $expense, $expense->company);
        }
    }

    /**
     * Handle the expense "restored" event.
     *
     * @param Expense $expense
     * @return void
     */
    public function restored(Expense $expense)
    {
        //
    }

    /**
     * Handle the expense "force deleted" event.
     *
     * @param Expense $expense
     * @return void
     */
    public function forceDeleted(Expense $expense)
    {
        //
    }
}
