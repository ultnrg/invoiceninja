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

namespace App\Providers;

use App\Http\Middleware\SetDomainNameDb;
use App\Models\Account;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyGateway;
use App\Models\CompanyToken;
use App\Models\Credit;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Proposal;
use App\Models\Quote;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;
use App\Observers\AccountObserver;
use App\Observers\ClientObserver;
use App\Observers\CompanyGatewayObserver;
use App\Observers\CompanyObserver;
use App\Observers\CompanyTokenObserver;
use App\Observers\CreditObserver;
use App\Observers\ExpenseObserver;
use App\Observers\InvoiceObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProductObserver;
use App\Observers\ProposalObserver;
use App\Observers\QuoteObserver;
use App\Observers\SubscriptionObserver;
use App\Observers\TaskObserver;
use App\Observers\UserObserver;
use App\Utils\Ninja;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        /* Limits the number of parallel jobs fired per minute when checking data*/
        RateLimiter::for('checkdata', function ($job) {
            return  Limit::perMinute(100);
        });

        Relation::morphMap([
            'invoices'  => Invoice::class,
          //  'credits'   => \App\Models\Credit::class,
            'proposals' => Proposal::class,
        ]);

        Blade::if('env', function ($environment) {
            return config('ninja.environment') === $environment;
        });

        Schema::defaultStringLength(191);

        Account::observe(AccountObserver::class);
        Subscription::observe(SubscriptionObserver::class);
        Client::observe(ClientObserver::class);
        Company::observe(CompanyObserver::class);
        CompanyGateway::observe(CompanyGatewayObserver::class);
        CompanyToken::observe(CompanyTokenObserver::class);
        Credit::observe(CreditObserver::class);
        Expense::observe(ExpenseObserver::class);
        Invoice::observe(InvoiceObserver::class);
        Payment::observe(PaymentObserver::class);
        Product::observe(ProductObserver::class);
        Proposal::observe(ProposalObserver::class);
        Quote::observe(QuoteObserver::class);
        Task::observe(TaskObserver::class);
        User::observe(UserObserver::class);


        /* Handles setting the correct database with livewire classes */
        if(Ninja::isHosted())
        {
            Livewire::addPersistentMiddleware([
                SetDomainNameDb::class,
            ]);
        }

        // Queue::before(function (JobProcessing $event) {
        //     // \Log::info('Event Job '.$event->connectionName);
        //     \Log::error('Event Job '.$event->job->getJobId);
        //     // \Log::info('Event Job '.$event->job->payload());
        // });
        //! Update Posted AT
        // Queue::after(function (JobProcessed $event) {
        //     // \Log::info('Event Job '.$event->connectionName);
        //     \Log::error('Event Job '.$event->job->getJobId);
        //     // \Log::info('Event Job '.$event->job->payload());
        // });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadHelpers();
    }

    protected function loadHelpers()
    {
        foreach (glob(__DIR__.'/../Helpers/*.php') as $filename) {
            require_once $filename;
        }
    }
}
