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

use App\Jobs\Util\UnlinkFile;
use App\Jobs\Util\WebhookHandler;
use App\Models\Quote;
use App\Models\Webhook;

class QuoteObserver
{
    /**
     * Handle the quote "created" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function created(Quote $quote)
    {
        $subscriptions = Webhook::where('company_id', $quote->company->id)
                        ->where('event_id', Webhook::EVENT_CREATE_QUOTE)
                        ->exists();

        if ($subscriptions) {
            WebhookHandler::dispatch(Webhook::EVENT_CREATE_QUOTE, $quote, $quote->company);
        }
    }

    /**
     * Handle the quote "updated" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function updated(Quote $quote)
    {
        $subscriptions = Webhook::where('company_id', $quote->company->id)
                        ->where('event_id', Webhook::EVENT_UPDATE_QUOTE)
                        ->exists();

        if ($subscriptions) {
            WebhookHandler::dispatch(Webhook::EVENT_UPDATE_QUOTE, $quote, $quote->company);
        }

    }

    /**
     * Handle the quote "deleted" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function deleted(Quote $quote)
    {
        $subscriptions = Webhook::where('company_id', $quote->company->id)
                        ->where('event_id', Webhook::EVENT_DELETE_QUOTE)
                        ->exists();

        if ($subscriptions) {
            WebhookHandler::dispatch(Webhook::EVENT_DELETE_QUOTE, $quote, $quote->company);
        }
    }

    /**
     * Handle the quote "restored" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function restored(Quote $quote)
    {
        //
    }

    /**
     * Handle the quote "force deleted" event.
     *
     * @param Quote $quote
     * @return void
     */
    public function forceDeleted(Quote $quote)
    {
        //
    }
}
