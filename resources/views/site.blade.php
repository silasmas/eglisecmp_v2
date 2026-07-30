<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Centre Missionnaire Philadelphie — L'amour fraternel au service des nations">
    @php
        $spaBasePath = parse_url((string) config('app.url'), PHP_URL_PATH);
        $spaBase = is_string($spaBasePath) ? rtrim($spaBasePath, '/') : '';
    @endphp
    @if ($spaBase !== '')
        <meta name="spa-base" content="{{ $spaBase }}">
    @endif
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
    <style>
        #root:empty {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #faf7f5;
            color: #57534e;
            font-family: system-ui, sans-serif;
        }
        #root:empty::before {
            content: "Chargement…";
            font-size: 14px;
        }
    </style>
    @vite(['resources/js/site/main.tsx'])
</head>
<body>
<div id="root"></div>
<script>
    window.addEventListener('error', function (event) {
        if (!event.filename || event.filename.indexOf('5173') === -1) {
            return;
        }
        var root = document.getElementById('root');
        if (!root || root.childElementCount > 0) {
            return;
        }
        root.innerHTML = '<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;font-family:system-ui,sans-serif;text-align:center">' +
            '<div><p style="font-weight:600;margin-bottom:8px">Les assets de développement n’ont pas chargé.</p>' +
            '<p style="opacity:.75;margin-bottom:16px;font-size:14px">Rechargez la page (le site basculera sur le build local).</p>' +
            '<button onclick="location.reload()" style="background:#7f1d1d;color:#fff;border:0;border-radius:12px;padding:12px 20px;font-weight:600;cursor:pointer">Recharger</button></div></div>';
    }, true);
</script>
</body>
</html>
