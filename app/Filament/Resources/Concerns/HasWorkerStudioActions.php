<?php

declare(strict_types=1);

namespace App\Filament\Resources\Concerns;

use App\Filament\Pages\PublicQrLinksPage;
use Filament\Actions\Action;

/**
 * Actions d’en-tête Ouvriers : studio badges + QR pages (nouvel onglet, session admin).
 */
trait HasWorkerStudioActions
{
    /**
     * @return list<Action>
     */
    protected function workerStudioHeaderActions(): array
    {
        return [
            Action::make('openBadgeStudio')
                ->label('Studio badges')
                ->icon('heroicon-o-paint-brush')
                ->color('primary')
                ->url(url('/admin/worker-badge-studio'))
                ->openUrlInNewTab()
                ->visible(fn (): bool => auth()->check()),
            Action::make('openQrLinks')
                ->label('QR pages (scan)')
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->url(PublicQrLinksPage::getUrl())
                ->openUrlInNewTab()
                ->visible(fn (): bool => PublicQrLinksPage::canAccess()),
        ];
    }
}
