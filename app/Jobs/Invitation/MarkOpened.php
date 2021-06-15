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

namespace App\Jobs\Invitation;

use App\Utils\Traits\NumberFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

//todo - ensure we are MultiDB Aware in dispatched jobs

class MarkOpened implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NumberFormatter;

    public $message_id;

    public $entity;

    /**
     * Create a new job instance.
     *
     * @param string $message_id
     * @param string $entity
     */
    public function __construct(string $message_id, string $entity)
    {
        $this->message_id = $message_id;

        $this->entity = $entity;
    }

    /**
     * Execute the job.
     *
     *
     * @return false
     */
    public function handle()
    {
        $invitation = $this->entity::with('user', 'contact')
                        ->whereMessageId($this->message_id)
                        ->first();

        if (! $invitation) {
            return false;
        }

        $invitation->opened_date = now();
        //$invitation->email_error = $error;
        $invitation->save();
    }
}
