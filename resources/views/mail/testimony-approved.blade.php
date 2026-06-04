<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Témoignage publié</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 560px; margin: 0 auto; padding: 24px;">
  @if(!empty($logoUrl))
    <p style="text-align: center;"><img src="{{ $logoUrl }}" alt="CMP" width="120" style="max-width: 120px; height: auto;"></p>
  @endif
  <h1 style="color: #950000; font-size: 22px;">Votre témoignage est en ligne</h1>
  <p>Bonjour,</p>
  <p>Nous avons le plaisir de vous informer que votre témoignage <strong>« {{ $testimony->title }} »</strong> a été validé et publié sur le mur de témoignages de l’église CMP.</p>
  <p style="text-align: center; margin: 32px 0;">
    <a href="{{ $wallUrl }}" style="display: inline-block; background: #950000; color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600;">Voir le mur de témoignages</a>
  </p>
  <p>Que Dieu continue de vous bénir.<br>Équipe CMP Philadelphie</p>
</body>
</html>
