<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\QrCodeGenerator;
use App\Support\PublicQrLinks;
use Symfony\Component\HttpFoundation\Response;

/**
 * Téléchargement PNG d'un QR code de page publique.
 */
final class PublicQrDownloadController extends Controller
{
    /**
     * Télécharge le QR PNG pour une clé de page connue.
     *
     * @param  string  $key  Clé définie dans PublicQrLinks.
     */
    public function __invoke(string $key, QrCodeGenerator $generator): Response
    {
        $item = collect(PublicQrLinks::all())->firstWhere('key', $key);

        abort_if($item === null, 404);

        $url = PublicQrLinks::absoluteUrl($item['path']);
        $result = $generator->build($url, 640);

        return response($result->getString(), 200, [
            'Content-Type' => $result->getMimeType(),
            'Content-Disposition' => 'attachment; filename="qr-cmp-'.$key.'.png"',
        ]);
    }
}
