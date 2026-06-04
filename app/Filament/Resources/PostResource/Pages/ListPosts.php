<?php

declare(strict_types=1);

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Services\Youtube\YoutubeChannelSyncService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

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
                ->modalDescription('Vidéos, shorts et playlists de la chaîne seront alignés sur les publications et événements (onglet Playlists).')
                ->action(function (YoutubeChannelSyncService $sync): void {
                    $result = $sync->sync(false);
                    if (! $result['ok']) {
                        Notification::make()->title($result['message'])->danger()->send();

                        return;
                    }
                    Notification::make()
                        ->title($result['message'])
                        ->body("Playlists : {$result['playlists']} — Vidéos : {$result['videos']}")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
