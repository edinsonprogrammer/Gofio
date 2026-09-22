<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    @php
        $defaultSeo = app(\App\Services\SeoService::class)->defaultMeta();
        $siteSettings = app(\App\Repositories\SiteSettingsRepository::class)->all();
    @endphp

    <!-- Cabecera: metadatos SEO, fuentes, estilos y scripts de la aplicación -->

    <head>

        <meta charset="utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="csrf-token" content="{{ csrf_token() }}">



        <title inertia>{{ $defaultSeo['title'] ?? config('app.name', 'Gofio') }}</title>

        <meta name="description" content="{{ $defaultSeo['description'] ?? '' }}">

        @if(!empty($defaultSeo['keywords']))

            <meta name="keywords" content="{{ $defaultSeo['keywords'] }}">

        @endif

        <meta name="robots" content="{{ $defaultSeo['robots'] ?? 'index,follow' }}">

        <link rel="canonical" href="{{ $defaultSeo['canonical'] ?? url('/login') }}">



        <meta property="og:title" content="{{ $defaultSeo['title'] ?? config('app.name', 'Gofio') }}">

        <meta property="og:description" content="{{ $defaultSeo['description'] ?? '' }}">

        <meta property="og:type" content="website">

        <meta property="og:url" content="{{ $defaultSeo['canonical'] ?? url('/login') }}">

        <meta property="og:site_name" content="{{ $defaultSeo['site_name'] ?? 'Gofio' }}">

        @if(!empty($defaultSeo['image']))

            <meta property="og:image" content="{{ $defaultSeo['image'] }}">

        @endif



        <meta name="twitter:card" content="summary_large_image">

        <meta name="twitter:title" content="{{ $defaultSeo['title'] ?? config('app.name', 'Gofio') }}">

        <meta name="twitter:description" content="{{ $defaultSeo['description'] ?? '' }}">



        @if(!empty($siteSettings['seo_google_site_verification']))
            <meta name="google-site-verification" content="{{ $siteSettings['seo_google_site_verification'] }}">
        @endif
        @if(!empty($siteSettings['seo_bing_site_verification']))
            <meta name="msvalidate.01" content="{{ $siteSettings['seo_bing_site_verification'] }}">
        @endif

        <link rel="alternate" type="application/rss+xml" title="Gofio RSS" href="{{ url('/feed.xml') }}">



        <link rel="preconnect" href="https://fonts.googleapis.com">

        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />



        @if(!empty($defaultSeo['json_ld']))

            <script type="application/ld+json">@json($defaultSeo['json_ld'])</script>

        @endif



        <!-- Assets compilados por Vite: hoja de estilos y bundle de entrada JS -->

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @inertiaHead

    </head>

    <body class="font-sans antialiased">

        <!-- Contenedor raíz donde Inertia monta la aplicación Vue -->

        @inertia

    </body>

</html>


