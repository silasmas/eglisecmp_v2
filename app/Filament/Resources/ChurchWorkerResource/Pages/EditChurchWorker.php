<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchWorkerResource\Pages;

use App\Filament\Resources\ChurchWorkerResource;
use App\Models\ChurchWorker;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d’un ouvrier (dossier complet + lien public de modification).
 */
class EditChurchWorker extends EditRecord
{
    protected static string $resource = ChurchWorkerResource::class;

    /**
     * Actions d’en-tête de la page d’édition.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ChurchWorkerResource::makeGenerateEditLinkAction(),
            Action::make('copyEditLink')
                ->label('Copier le lien actuel')
                ->icon('heroicon-o-clipboard-document')
                ->color('gray')
                ->visible(fn (): bool => $this->record instanceof ChurchWorker && $this->record->hasValidEditToken())
                ->action(function (): void {
                    if (! $this->record instanceof ChurchWorker) {
                        return;
                    }
                    $url = $this->record->profileEditUrl() ?? '';
                    Notification::make()
                        ->title('Lien de modification')
                        ->body($url)
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Action::make('openBadge')
                ->label('Voir badge')
                ->icon('heroicon-o-identification')
                ->url(fn (): ?string => $this->record instanceof ChurchWorker && $this->record->badge_generated
                    ? route('workers.badge.public', ['token' => $this->record->badge_token])
                    : null)
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record instanceof ChurchWorker && $this->record->badge_generated),
        ];
    }
}
