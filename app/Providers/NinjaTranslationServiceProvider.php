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

use App\Helpers\Language\NinjaTranslator;
use Illuminate\Translation\TranslationServiceProvider;

class NinjaTranslationServiceProvider extends TranslationServiceProvider
{
    public function boot()
    {

        /*
         * To reset the translator instance we call
         *
         * App::forgetInstance('translator');
         *
         * Why? As the translator is a singleton it persists for its
         * lifecycle
         *
         * We _must_ reset the singleton when shifting between
         * clients/companies otherwise translations will
         * persist.
         *
         */

        // $this->app->bind('translator', function($app) {

        //     $loader = $app['translation.loader'];
        //     $locale = $app['config']['app.locale'];

        //     $trans = new NinjaTranslator($loader, $locale);

        //     $trans->setFallback($app['config']['app.fallback_locale']);

        //     return $trans;

        // });
        
        $this->app->singleton('translator', function ($app) {

            $loader = $app['translation.loader'];
            $locale = $app['config']['app.locale'];

            $trans = new NinjaTranslator($loader, $locale);

            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;

        });
    }

}
