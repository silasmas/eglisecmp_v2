<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\QrCodeGenerator;
use App\Support\PublicQrLinks;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Admin : génère et télécharge les QR codes des pages accessibles par lien / scan.
 */
class PublicQrLinksPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'QR pages (scan)';

    protected static ?string $title = 'QR codes — pages accessibles par scan / lien direct';

    protected static string|UnitEnum|null $navigationGroup = 'Ouvriers';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.public-qr-links';

    /**
     * @return array{links: list<array{key: string, label: string, description: string, path: string, url: string, qrDataUri: string}>}
     */
    protected function getViewData(): array
    {
        $generator = app(QrCodeGenerator::class);
        $links = [];

        foreach (PublicQrLinks::all() as $item) {
            $url = PublicQrLinks::absoluteUrl($item['path']);
            $links[] = [
                ...$item,
                'url' => $url,
                'qrDataUri' => $generator->dataUri($url, 280),
            ];
        }

        return ['links' => $links];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->hasRole('super_admin')
            || $user->can('ViewAny:ChurchWorker')
            || $user->can('page_PublicQrLinksPage')
        );
    }
}
