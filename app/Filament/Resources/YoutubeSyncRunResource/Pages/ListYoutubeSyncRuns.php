<?php

declare(strict_types=1);

namespace App\Filament\Resources\YoutubeSyncRunResource\Pages;

use App\Filament\Resources\YoutubeSyncRunResource;
use App\Services\Youtube\YoutubeSyncLauncher;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

/**
 * Liste des synchronisations YouTube avec actions de lancement manuel.
 */
class ListYoutubeSyncRuns extends ListRecords
{
    protected static string $resource = YoutubeSyncRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncIncremental')
                ->label('Lancer une synchro')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Synchronisation incrémentale')
                ->modalDescription(
                    'Importe les nouvelles vidéos et playlists depuis YouTube. '
                    .'Le traitement s’exécute en arrière-plan : cette page se rafraîchit toutes les 15 secondes.'
                )
                ->action(function (): void {
                    $userId = Auth::id();
                    $result = YoutubeSyncLauncher::launch(
                        'filament',
                        false,
                        is_numeric($userId) ? (int) $userId : null,
                    );
                    YoutubeSyncLauncher::notifyFilament($result);
                }),
            Action::make('syncFull')
                ->label('Synchro complète')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Synchronisation complète')
                ->modalDescription(
                    'Rescanne toutes les playlists (plus long). À utiliser après un problème ou une grosse mise à jour chaîne.'
                )
                ->action(function (): void {
                    $userId = Auth::id();
                    $result = YoutubeSyncLauncher::launch(
                        'filament',
                        true,
                        is_numeric($userId) ? (int) $userId : null,
                    );
                    YoutubeSyncLauncher::notifyFilament($result);
                }),
        ];
    }
}
