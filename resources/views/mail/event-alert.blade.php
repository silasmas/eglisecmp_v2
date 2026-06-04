<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>{{ $alertLabel }}</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.5; color: #1a1a1a;">
  @if(!empty($logoUrl))
    <p><img src="{{ $logoUrl }}" alt="CMP Philadelphie" width="120" style="max-width: 120px;"></p>
  @endif
  <h1 style="font-size: 20px;">{{ $alertLabel }}</h1>
  <h2 style="font-size: 18px; color: #950000;">{{ $eventTitle }}</h2>
  @if($eventDate !== '')
    <p><strong>Date :</strong> {{ $eventDate }}</p>
  @endif
  @if($eventTime !== '')
    <p><strong>Horaire :</strong> {{ $eventTime }}</p>
  @endif
  <p><strong>Lieu :</strong> {{ $location }}</p>
  <p>
    <a href="{{ $eventsUrl }}" style="display: inline-block; padding: 10px 18px; background: #950000; color: #fff; text-decoration: none; border-radius: 6px;">
      Voir sur le site
    </a>
  </p>
  <p style="font-size: 12px; color: #666; margin-top: 24px;">
    <a href="{{ $unsubscribeUrl }}">Se désabonner des alertes</a>
  </p>
</body>
</html>
