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

namespace App\Console;

use App\Jobs\Cron\AutoBillCron;
use App\Jobs\Cron\RecurringInvoicesCron;
use App\Jobs\Cron\SubscriptionCron;
use App\Jobs\Ninja\AdjustEmailQuota;
use App\Jobs\Ninja\CompanySizeCheck;
use App\Jobs\Util\DiskCleanup;
use App\Jobs\Util\ReminderJob;
use App\Jobs\Util\SchedulerCheck;
use App\Jobs\Util\SendFailedEmails;
use App\Jobs\Util\UpdateExchangeRates;
use App\Jobs\Util\VersionCheck;
use App\Utils\Ninja;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

        $schedule->job(new VersionCheck)->daily();

        $schedule->job(new DiskCleanup)->daily()->withoutOverlapping();

        $schedule->command('ninja:check-data --database=db-ninja-01')->daily()->withoutOverlapping();

        $schedule->job(new ReminderJob)->hourly()->withoutOverlapping();

        $schedule->job(new CompanySizeCheck)->daily()->withoutOverlapping();

        $schedule->job(new UpdateExchangeRates)->daily()->withoutOverlapping();

        $schedule->job(new SubscriptionCron)->daily()->withoutOverlapping();

        $schedule->job(new RecurringInvoicesCron)->hourly()->withoutOverlapping();
        
        $schedule->job(new AutoBillCron)->dailyAt('00:30')->withoutOverlapping();        

        $schedule->job(new SchedulerCheck)->everyFiveMinutes();

        /* Run hosted specific jobs */
        if (Ninja::isHosted()) {

            $schedule->job(new AdjustEmailQuota)->daily()->withoutOverlapping();
            $schedule->job(new SendFailedEmails)->daily()->withoutOverlapping();
            $schedule->command('ninja:check-data --database=db-ninja-02')->daily()->withoutOverlapping();

        }

        if(config('queue.default') == 'database' && Ninja::isSelfHost() && config('ninja.internal_queue_enabled') && !config('ninja.is_docker')) {

            $schedule->command('queue:work')->everyMinute()->withoutOverlapping();
            $schedule->command('queue:restart')->everyFiveMinutes()->withoutOverlapping(); 
            
        }

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
