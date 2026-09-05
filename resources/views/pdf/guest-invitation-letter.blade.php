<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Lettre d'invitation — {{ $projectTitle }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; margin: 0; font-size: 12pt; line-height: 1.45; }
        .header {
            background: #2b1a12;
            color: #fff;
            padding: 18px 28px 28px;
            min-height: 90px;
        }
        .header h1 { margin: 0; font-size: 18pt; color: #f5c542; }
        .header .dates { margin-top: 6px; font-size: 10pt; color: #f0e6d8; }
        .body { padding: 36px 40px 24px; }
        .recipient { color: #7b1d3e; font-size: 14pt; font-weight: bold; margin-bottom: 18px; }
        .signature { margin-top: 28px; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #e87722;
            color: #fff;
            padding: 10px 28px;
            font-size: 9pt;
        }
        .footer .site { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $projectTitle }}</h1>
        @if(!empty($dates))
            <div class="dates">{{ $dates }}</div>
        @endif
    </div>
    <div class="body">
        @if(!empty($recipientTitle))
            <div class="recipient">{!! $recipientTitle !!}</div>
        @endif
        <div class="content">{!! $bodyHtml !!}</div>
        @if(!empty($signatureHtml))
            <div class="signature">{!! $signatureHtml !!}</div>
        @endif
    </div>
    <div class="footer">
        <span>Centre Missionnaire Philadelphie</span>
        <span class="site">www.eglisecmp.com</span>
    </div>
</body>
</html>
