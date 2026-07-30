<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Code de vérification CMP</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:Inter,Segoe UI,Arial,sans-serif;color:#18181b;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.08);">
          <tr>
            <td style="background:#7b1d3e;padding:22px 28px;text-align:center;">
              @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="CMP" width="72" style="display:block;margin:0 auto 10px;max-width:72px;border:0;">
              @endif
              <p style="margin:0;color:#ffffff;font-size:13px;letter-spacing:.08em;text-transform:uppercase;font-weight:700;">
                {{ $siteName }}
              </p>
              <h1 style="margin:8px 0 0;color:#ffffff;font-size:22px;line-height:1.25;font-weight:800;">
                Vérification de votre e-mail
              </h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 28px 10px;">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.55;color:#3f3f46;">
                Bonjour,<br>
                Voici votre code pour finaliser votre <strong>inscription ouvrier</strong>.
                Saisissez-le sur le formulaire — il expire dans <strong>{{ $ttlMinutes }}&nbsp;minutes</strong>.
              </p>
              <div style="margin:22px 0;padding:18px 16px;border-radius:14px;background:#fdf2f4;border:1px solid #f4a9ba;text-align:center;">
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#ab1e47;">
                  Votre code
                </p>
                <p style="margin:0;font-size:34px;letter-spacing:.28em;font-weight:800;color:#7b1d3e;font-family:Consolas,Monaco,monospace;">
                  {{ $code }}
                </p>
              </div>
              <p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#71717a;">
                Si vous n’avez pas demandé ce code, ignorez simplement cet e-mail.
              </p>
            </td>
          </tr>
          <tr>
            <td style="padding:8px 28px 26px;text-align:center;">
              <p style="margin:0;font-size:11px;color:#a1a1aa;">
                © {{ date('Y') }} Centre Missionnaire Philadelphie — Kinshasa
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
