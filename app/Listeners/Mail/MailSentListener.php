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

namespace App\Listeners\Mail;

use App\Libraries\MultiDB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Mail\Events\MessageSent;

class MailSentListener implements ShouldQueue
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
    public function handle(MessageSent $event)
    {
        
        if(property_exists($event->message, 'invitation') && $event->message->invitation){

            MultiDB::setDb($event->message->invitation->company->db);

            if($event->message->getHeaders()->get('x-pm-message-id')){

                $postmark_id = $event->message->getHeaders()->get('x-pm-message-id')->getValue();

                // nlog($postmark_id);
                $invitation = $event->message->invitation;
                $invitation->message_id = $postmark_id;
                $invitation->save();

            }

        }

    }
}
