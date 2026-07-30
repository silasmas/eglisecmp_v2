<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Badge ouvrier — Centre Missionnaire Philadelphie</title>
  <meta name="description" content="Badge de service ouvrier du Centre Missionnaire Philadelphie.">

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('worker-badge-studio/css/tokens.css') }}">
  <link rel="stylesheet" href="{{ asset('worker-badge-studio/css/base.css') }}">
  <link rel="stylesheet" href="{{ asset('worker-badge-studio/css/badge.css') }}">
  <link rel="stylesheet" href="{{ asset('worker-badge-studio/css/buttons.css') }}">
  <link rel="stylesheet" href="{{ asset('worker-badge-studio/css/utilities.css') }}">
  <link rel="stylesheet" href="{{ asset('worker-badge-studio/css/pages.css') }}">

  <script>
    window.CMP_BADGE_PUBLIC_URL = @json($badgePublicUrl);
    window.CMP_BADGE_TOKEN = @json($token);
  </script>

  @vite(['resources/js/site/worker-badge-entry.tsx'])
</head>
<body class="print-a6-page worker-badge-module">
  <div id="worker-badge-root" data-token="{{ $token }}"></div>
</body>
</html>
