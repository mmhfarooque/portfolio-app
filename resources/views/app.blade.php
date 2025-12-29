<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Photography Portfolio') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

        <!-- Site Verification -->
        @if($googleVerification = \App\Models\Setting::get('seo_google_verification'))
        <meta name="google-site-verification" content="{{ $googleVerification }}" />
        @endif
        @if($bingVerification = \App\Models\Setting::get('seo_bing_verification'))
        <meta name="msvalidate.01" content="{{ $bingVerification }}" />
        @endif

        <!-- Robots Control -->
        @if(\App\Models\Setting::get('seo_robots_allow') !== '1')
        <meta name="robots" content="noindex, nofollow" />
        @endif

        <!-- Google Tag Manager -->
        @if($gtmId = \App\Models\Setting::get('seo_gtm_id'))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
        @endif

        <!-- Google Analytics 4 -->
        @if($gaId = \App\Models\Setting::get('seo_google_analytics'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $gaId }}');
        </script>
        @endif

        <!-- Facebook Pixel -->
        @if($fbPixel = \App\Models\Setting::get('seo_facebook_pixel'))
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{{ $fbPixel }}');
            fbq('track', 'PageView');
        </script>
        <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $fbPixel }}&ev=PageView&noscript=1"/></noscript>
        @endif

        <!-- Custom Head Scripts -->
        @if($customHeadScripts = \App\Models\Setting::get('seo_custom_head_scripts'))
        {!! $customHeadScripts !!}
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', 'resources/css/app.css'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <!-- Google Tag Manager (noscript) -->
        @if($gtmId = \App\Models\Setting::get('seo_gtm_id'))
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        @endif

        @inertia

        <!-- Custom Body Scripts -->
        @if($customBodyScripts = \App\Models\Setting::get('seo_custom_body_scripts'))
        {!! $customBodyScripts !!}
        @endif
    </body>
</html>
