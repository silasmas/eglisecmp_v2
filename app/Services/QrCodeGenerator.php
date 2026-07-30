<?php

declare(strict_types=1);

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

/**
 * Génération de QR codes PNG pour les liens publics CMP.
 */
final class QrCodeGenerator
{
    /**
     * Construit un QR code pour une URL.
     *
     * @param  string  $data  Contenu (URL).
     * @param  int  $size  Taille en pixels.
     */
    public function build(string $data, int $size = 320): ResultInterface
    {
        $builder = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: $size,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return $builder->build();
    }

    /**
     * Data URI utilisable dans une balise img.
     */
    public function dataUri(string $data, int $size = 320): string
    {
        return $this->build($data, $size)->getDataUri();
    }
}
