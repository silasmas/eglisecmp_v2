<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subjectLine ?? 'CMP Philadelphie' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f1f1;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f1f1;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(69,10,10,0.12);">
          <tr>
            <td style="background:linear-gradient(135deg,#6b0f1a 0%,#8b1a2b 55%,#7b1d3e 100%);padding:28px 32px;text-align:center;">
              @if(! empty($logoPath) && is_file($logoPath))
                <img src="{{ $message->embed($logoPath) }}" alt="Centre Missionnaire Philadelphie" width="110" style="display:block;margin:0 auto 14px;border:0;max-width:110px;height:auto;background:#ffffff;border-radius:12px;padding:8px;">
              @elseif(! empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="Centre Missionnaire Philadelphie" width="110" style="display:block;margin:0 auto 14px;border:0;max-width:110px;height:auto;background:#ffffff;border-radius:12px;padding:8px;">
              @endif
              <p style="margin:0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#f5d0d4;">Centre Missionnaire Philadelphie</p>
              <h1 style="margin:10px 0 0;font-size:20px;line-height:1.35;font-weight:700;color:#ffffff;">{{ $heading }}</h1>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 32px 8px;">
              @if(! empty($pastorPhotoPath) && is_file($pastorPhotoPath))
                <div style="text-align:center;margin-bottom:18px;">
                  <img src="{{ $message->embed($pastorPhotoPath) }}" alt="{{ $pastorName }}" width="96" height="96" style="width:96px;height:96px;border-radius:999px;object-fit:cover;border:3px solid #ea7e2d;display:inline-block;">
                </div>
              @elseif(! empty($pastorPhotoUrl))
                <div style="text-align:center;margin-bottom:18px;">
                  <img src="{{ $pastorPhotoUrl }}" alt="{{ $pastorName }}" width="96" height="96" style="width:96px;height:96px;border-radius:999px;object-fit:cover;border:3px solid #ea7e2d;display:inline-block;">
                </div>
              @endif

              <p style="margin:0 0 16px;font-size:15px;line-height:1.55;color:#4b5563;">
                {!! $introHtml !!}
              </p>

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border-collapse:separate;border-spacing:0;">
                <tr>
                  <td style="padding:12px 14px;background:#fafafa;border:1px solid #ececec;border-radius:10px 10px 0 0;font-size:12px;color:#6b7280;width:110px;">Pasteur</td>
                  <td style="padding:12px 14px;background:#ffffff;border:1px solid #ececec;border-left:0;border-radius:0 10px 0 0;font-size:14px;font-weight:700;color:#111827;">{{ $pastorName }}</td>
                </tr>
                @if(! empty($projectTitle))
                  <tr>
                    <td style="padding:12px 14px;background:#fafafa;border:1px solid #ececec;border-top:0;font-size:12px;color:#6b7280;">Projet</td>
                    <td style="padding:12px 14px;background:#ffffff;border:1px solid #ececec;border-left:0;border-top:0;font-size:14px;color:#111827;">{{ $projectTitle }}</td>
                  </tr>
                @endif
                @foreach(($metaRows ?? []) as $row)
                  <tr>
                    <td style="padding:12px 14px;background:#fafafa;border:1px solid #ececec;border-top:0;font-size:12px;color:#6b7280;">{{ $row['label'] }}</td>
                    <td style="padding:12px 14px;background:#ffffff;border:1px solid #ececec;border-left:0;border-top:0;font-size:14px;color:#111827;">{{ $row['value'] }}</td>
                  </tr>
                @endforeach
              </table>

              @if(! empty($passwordHint))
                <p style="margin:0 0 18px;font-size:13px;color:#7f1d1d;background:#fdf2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 14px;">
                  <strong>Mot de passe :</strong> {{ $passwordHint }}
                </p>
              @endif

              <table role="presentation" cellspacing="0" cellpadding="0" align="center" style="margin:8px auto 20px;">
                <tr>
                  <td style="border-radius:999px;background:#6b0f1a;">
                    <a href="{{ $ctaUrl }}" style="display:inline-block;padding:12px 28px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">
                      {{ $ctaLabel }}
                    </a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:16px 32px 28px;text-align:center;border-top:1px solid #f3f4f6;">
              <p style="margin:0;font-size:12px;line-height:1.5;color:#9ca3af;">
                Centre Missionnaire Philadelphie · Kinshasa<br>
                info@cm-philadelphie.org
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
