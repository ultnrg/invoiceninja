<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <!-- Error: {{ session('error') }} -->

        @if (config('services.analytics.tracking_id'))
            <script async src="https://www.googletagmanager.com/gtag/js?id=UA-122229484-1"></script>
            <script>
                window.dataLayer = window.dataLayer || [];

                function gtag() {
                    dataLayer.push(arguments);
                }

                gtag('js', new Date());
                gtag('config', '{{ config('services.analytics.tracking_id') }}', {'anonymize_ip': true});

                function trackEvent(category, action) {
                    ga('send', 'event', category, action, this.src);
                }
            </script>
            <script>
                Vue.config.devtools = true;
            </script>
        @else
            <script>
                function gtag() {
                }
            </script>
        @endif


        <!-- Title -->
        @auth()
            <title>@yield('meta_title', '') — {{ auth('contact')->user()->user->account->isPaid() ? auth('contact')->user()->company->present()->name() : 'Invoice Ninja' }}</title>
        @endauth

        @guest
            <title>@yield('meta_title', '') — {{ config('app.name') }}</title>
        @endguest

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="@yield('meta_description')"/>
        
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Scripts -->
        <script src="{{ mix('js/app.js') }}" defer></script>
        <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.7.x/dist/alpine.min.js" defer></script>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="https://fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <link href="{{ mix('css/app.css') }}" rel="stylesheet">

        @if(!auth('contact')->user()->user->account->isPaid())
            <link href="{{ asset('favicon.png') }}" rel="shortcut icon" type="image/png">
        @endif

        <link rel="canonical" href="{{ config('ninja.site_url') }}/{{ request()->path() }}"/>

        @if((bool) \App\Utils\Ninja::isSelfHost())
            <style>
                {!! $client->getSetting('portal_custom_css') !!}
            </style>
        @endif

        @livewireStyles

        {{-- Feel free to push anything to header using @push('header') --}}
        @stack('head')

        @if((bool) \App\Utils\Ninja::isSelfHost() && !empty($client->getSetting('portal_custom_head')))
            <div class="py-1 text-sm text-center text-white bg-primary">
                {!! $client->getSetting('portal_custom_head') !!}
            </div>
        @endif

        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css" />
    </head>

    @include('portal.ninja2020.components.primary-color')

    <body class="antialiased">
        @if(session()->has('message'))
            <div class="py-1 text-sm text-center text-white bg-primary disposable-alert">
                {{ session('message') }}
            </div>
        @endif

        @component('portal.ninja2020.components.general.sidebar.main')
            @yield('body')
        @endcomponent

        @livewireScripts

        <script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js" data-cfasync="false"></script>
        <script>
            window.addEventListener("load", function(){
                if (! window.cookieconsent) {
                    return;
                }
                window.cookieconsent.initialise({
                    "palette": {
                        "popup": {
                            "background": "#000"
                        },
                        "button": {
                            "background": "#f1d600"
                        },
                    },
                    "content": {
                        "href": "https://www.invoiceninja.com/privacy-policy/",
                        "message": "This website uses cookies to ensure you get the best experience on our website.",
                        "dismiss": "Got it!",
                        "link": "Learn more",
                    }
                })}
            );
        </script>
    </body>

    <footer>
        @yield('footer')
        @stack('footer')

        @if((bool) \App\Utils\Ninja::isSelfHost() && !empty($client->getSetting('portal_custom_footer')))
            <div class="py-1 text-sm text-center text-white bg-primary">
                {!! $client->getSetting('portal_custom_footer') !!}
            </div>
        @endif
    </footer>

    @if((bool) \App\Utils\Ninja::isSelfHost())
        <script>
            {!! $client->getSetting('portal_custom_js') !!}
        </script>
    @endif
</html>
