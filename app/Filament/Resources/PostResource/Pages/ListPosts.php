<?php

declare(strict_types=1);

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Jobs\SyncYoutubeChannelJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Config;

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
                    .'Vous pouvez fermer cette page : la synchro continue. Actualisez la liste ensuite.'
                )
                ->action(function (): void {
                    $queue = (string) Config::get('queue.default', 'sync');

                    if ($queue === 'database') {
                        SyncYoutubeChannelJob::dispatch();

                        Notification::make()
                            ->title('Synchronisation YouTube lancée')
                            ->body('Le traitement s’exécute en arrière-plan via la file. Assurez-vous que queue:work tourne.')
                            ->success()
                            ->send();

                        return;
                    }

                    $this->runYoutubeSyncInBackgroundProcess();
                }),
            CreateAction::make(),
        ];
    }

    /**
     * Repli si QUEUE_CONNECTION=sync : lance artisan en processus détaché (évite le 504 nginx).
     */
    private function runYoutubeSyncInBackgroundProcess(): void
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            pclose(popen('start /B "" "'.$php.'" "'.$artisan.'" youtube:sync', 'r'));

            Notification::make()
                ->title('Synchronisation YouTube lancée')
                ->body('Le traitement tourne en arrière-plan sur le serveur. Actualisez la liste dans 10–20 minutes.')
                ->success()
                ->send();

            return;
        }

        $logFile = storage_path('logs/youtube-sync.log');
        $command = sprintf(
            'nohup %s %s youtube:sync >> %s 2>&1 &',
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg($logFile)
        );
        exec($command);

        Notification::make()
            ->title('Synchronisation YouTube lancée')
            ->body('Consultez storage/logs/youtube-sync.log si besoin. Actualisez la liste dans 10–20 minutes.')
            ->success()
            ->send();
    }
}
