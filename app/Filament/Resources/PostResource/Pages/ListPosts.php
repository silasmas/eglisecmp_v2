<?php

declare(strict_types=1);

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Services\Youtube\YoutubeSyncLauncher;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

/**
 * Liste des publications avec action de synchronisation YouTube.
 */
class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncYoutube')
                ->label('Synchroniser YouTube')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Importer depuis YouTube')
                ->modalDescription(
                    'Les vidéos et playlists seront importées en arrière-plan (10 à 20 minutes). '
                    .'Suivez l’avancement dans Admin → Synchronisations YouTube.'
                )
                ->action(function (): void {
                    $userId = Auth::id();
                    $result = YoutubeSyncLauncher::launch(
                        'posts_page',
                        false,
                        is_numeric($userId) ? (int) $userId : null,
                    );
                    YoutubeSyncLauncher::notifyFilament($result);
                }),
            CreateAction::make(),
        ];
    }
}
