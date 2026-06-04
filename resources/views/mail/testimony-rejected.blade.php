<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Témoignage non publié</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; line-height: 1.6; color: #1f2937; max-width: 560px; margin: 0 auto; padding: 24px;">
  @if(!empty($logoUrl))
    <p style="text-align: center;"><img src="{{ $logoUrl }}" alt="CMP" width="120" style="max-width: 120px; height: auto;"></p>
  @endif
  <h1 style="color: #950000; font-size: 22px;">Décision concernant votre témoignage</h1>
  <p>Bonjour,</p>
  <p>Après examen, nous ne pouvons pas publier votre témoignage <strong>« {{ $testimony->title }} »</strong> sur le mur pour le moment.</p>
  @if($reason !== '')
    <p><strong>Motif :</strong></p>
    <p style="background: #f3f4f6; padding: 16px; border-radius: 8px;">{{ $reason }}</p>
  @endif
  <p>Vous pouvez nous contacter ou soumettre une nouvelle version si vous le souhaitez.</p>
  <p>Équipe CMP Philadelphie</p>
</body>
</html>
