<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Centre Missionnaire Philadelphie — L'amour fraternel au service des nations">
    <title>Église CMP — Centre Missionnaire Philadelphie</title>
    <link rel="icon" href="/favicon.ico">
    @php($googleAnalyticsId = config('services.google_analytics.measurement_id'))
    @if (filled($googleAnalyticsId))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() {dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ $googleAnalyticsId }}');
        </script>
    @endif
    @vite(['resources/js/site/main.tsx'])
</head>
<body>
<div id="root"></div>
</body>
</html>
