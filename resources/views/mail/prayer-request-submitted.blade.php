<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nouvelle requête de prière</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f1f1;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f1f1;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(69,10,10,0.12);">
          {{-- En-tête bordeaux --}}
          <tr>
            <td style="background:linear-gradient(135deg,#6b0f1a 0%,#8b1a2b 100%);padding:28px 32px;text-align:center;">
              @if(! empty($logoCid))
                <img src="{{ $logoCid }}" alt="Centre Missionnaire Philadelphie" width="120" style="display:block;margin:0 auto 16px;border:0;max-width:120px;height:auto;background:#ffffff;border-radius:12px;padding:8px;">
              @elseif(! empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="Centre Missionnaire Philadelphie" width="120" style="display:block;margin:0 auto 16px;border:0;max-width:120px;height:auto;background:#ffffff;border-radius:12px;padding:8px;">
              @endif
              <p style="margin:0;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;color:#f5d0d4;">Intercession</p>
              <h1 style="margin:8px 0 0;font-size:22px;line-height:1.3;font-weight:700;color:#ffffff;">Nouvelle requête de prière</h1>
            </td>
          </tr>

          {{-- Corps --}}
          <tr>
            <td style="padding:28px 32px 12px;">
              <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#4b5563;">
                Une intention vient d’être déposée sur le site public.
              </p>

              @if($inquiry->is_anonymous)
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;">
                  <tr>
                    <td style="padding:10px 14px;background:#fdf2f2;border:1px solid #fecaca;border-radius:10px;">
                      <span style="display:inline-block;font-size:12px;font-weight:700;color:#991b1b;text-transform:uppercase;letter-spacing:0.06em;">Anonyme</span>
                      <span style="font-size:13px;color:#7f1d1d;margin-left:8px;">Demande confidentielle</span>
                    </td>
                  </tr>
                </table>
              @else
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:20px;border-collapse:separate;border-spacing:0;">
                  <tr>
                    <td style="padding:12px 14px;background:#fafafa;border:1px solid #ececec;border-radius:10px 10px 0 0;font-size:12px;color:#6b7280;width:90px;">Nom</td>
                    <td style="padding:12px 14px;background:#ffffff;border:1px solid #ececec;border-left:0;border-radius:0 10px 0 0;font-size:14px;font-weight:600;color:#111827;">{{ $displayName }}</td>
                  </tr>
                  @if(filled($inquiry->phone))
                    <tr>
                      <td style="padding:12px 14px;background:#fafafa;border:1px solid #ececec;border-top:0;font-size:12px;color:#6b7280;">Tél.</td>
                      <td style="padding:12px 14px;background:#ffffff;border:1px solid #ececec;border-left:0;border-top:0;font-size:14px;color:#111827;">{{ $inquiry->phone }}</td>
                    </tr>
                  @endif
                  @if(filled($inquiry->country))
                    <tr>
                      <td style="padding:12px 14px;background:#fafafa;border:1px solid #ececec;border-top:0;font-size:12px;color:#6b7280;">Pays</td>
                      <td style="padding:12px 14px;background:#ffffff;border:1px solid #ececec;border-left:0;border-top:0;font-size:14px;color:#111827;">{{ $inquiry->country }}</td>
                    </tr>
                  @endif
                  @if(filled($inquiry->email))
                    <tr>
                      <td style="padding:12px 14px;background:#fafafa;border:1px solid #ececec;border-top:0;border-radius:0 0 0 10px;font-size:12px;color:#6b7280;">Email</td>
                      <td style="padding:12px 14px;background:#ffffff;border:1px solid #ececec;border-left:0;border-top:0;border-radius:0 0 10px 0;font-size:14px;color:#111827;">{{ $inquiry->email }}</td>
                    </tr>
                  @endif
                </table>
              @endif

              <p style="margin:0 0 8px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#8b1a2b;">Requête</p>
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                <tr>
                  <td style="padding:16px 18px;background:#fdf8f8;border-left:4px solid #8b1a2b;border-radius:0 10px 10px 0;font-size:15px;line-height:1.65;color:#374151;font-style:italic;">
                    «&nbsp;{{ $inquiry->message }}&nbsp;»
                  </td>
                </tr>
              </table>

              <table role="presentation" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto 8px;">
                <tr>
                  <td style="border-radius:999px;background:#6b0f1a;">
                    <a href="{{ $adminUrl }}" style="display:inline-block;padding:14px 28px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">Voir dans l’administration</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          {{-- Pied --}}
          <tr>
            <td style="padding:16px 32px 28px;text-align:center;border-top:1px solid #f0f0f0;">
              <p style="margin:0;font-size:12px;line-height:1.5;color:#9ca3af;">
                Centre Missionnaire Philadelphie · Gombe, Kinshasa
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
