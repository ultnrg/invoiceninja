<?php
/**
 * Quote Ninja (https://quoteninja.com).
 *
 * @link https://github.com/quoteninja/quoteninja source repository
 *
 * @copyright Copyright (c) 2021. Quote Ninja LLC (https://quoteninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Listeners\Quote;

use App\Jobs\Mail\NinjaMailer;
use App\Jobs\Mail\NinjaMailerJob;
use App\Jobs\Mail\NinjaMailerObject;
use App\Libraries\MultiDB;
use App\Mail\Admin\EntityCreatedObject;
use App\Notifications\Admin\EntitySentNotification;
use App\Utils\Traits\Notifications\UserNotifies;
use Illuminate\Contracts\Queue\ShouldQueue;

class QuoteCreatedNotification implements ShouldQueue
{
    use UserNotifies;

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

        $first_notification_sent = true;

        $quote = $event->quote;

        $nmo = new NinjaMailerObject;
        $nmo->mailable = new NinjaMailer( (new EntityCreatedObject($quote, 'quote'))->build() );
        $nmo->company = $quote->company;
        $nmo->settings = $quote->company->settings;

        /* We loop through each user and determine whether they need to be notified */
        foreach ($event->company->company_users as $company_user) {

            /* The User */
            $user = $company_user->user;

            /* This is only here to handle the alternate message channels - ie Slack */
            // $notification = new EntitySentNotification($event->invitation, 'quote');

            /* Returns an array of notification methods */
            $methods = $this->findUserNotificationTypes($quote->invitations()->first(), $company_user, 'quote', ['all_notifications', 'quote_created', 'quote_created_all']);

            /* If one of the methods is email then we fire the EntitySentMailer */
            if (($key = array_search('mail', $methods)) !== false && $first_notification_sent === true) {
                unset($methods[$key]);

                
                $nmo->to_user = $user;

                NinjaMailerJob::dispatch($nmo);
                
                /* This prevents more than one notification being sent */
                $first_notification_sent = false;
            }

            /* Override the methods in the Notification Class */
            // $notification->method = $methods;

            //  Notify on the alternate channels 
            // $user->notify($notification);
        }
    }
}
