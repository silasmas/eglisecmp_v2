<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Live CMP Philadelphie</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.5; color: #1a1a1a;">
  @if(!empty($logoUrl))
    <p><img src="{{ $logoUrl }}" alt="CMP Philadelphie" width="120" style="max-width: 120px;"></p>
  @endif
  <h1 style="font-size: 20px;">Un live a commencé</h1>
  <p>{{ $live['title'] ?? 'Diffusion en direct' }}</p>
  <p>
    <a href="{{ $live['watchUrl'] ?? $siteUrl }}" style="display: inline-block; padding: 10px 18px; background: #b91c1c; color: #fff; text-decoration: none; border-radius: 6px;">
      Regarder sur YouTube
    </a>
  </p>
  <p style="font-size: 14px; color: #555;">
    Vous pouvez aussi ouvrir le site :
    <a href="{{ $siteUrl }}">{{ $siteUrl }}</a>
  </p>
  @if(!empty($unsubscribeUrl))
    <p style="font-size: 12px; color: #666; margin-top: 24px;">
      <a href="{{ $unsubscribeUrl }}">Se désabonner des alertes</a>
    </p>
  @endif
</body>
</html>
