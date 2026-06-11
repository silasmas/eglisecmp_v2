<?php

declare(strict_types=1);

namespace App\Services\Youtube;

use App\Jobs\SyncYoutubeChannelJob;
use App\Models\YoutubeSyncRun;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Démarre une synchronisation YouTube en arrière-plan (évite le timeout HTTP admin).
 */
final class YoutubeSyncLauncher
{
    /**
     * Planifie une synchro et retourne le run créé.
     *
     * @param  string  $source  Origine (filament, posts_page…).
     * @param  bool  $full  Passe complète.
     * @param  int|null  $userId  ID utilisateur admin.
     * @return array{ok: bool, run: YoutubeSyncRun|null, message: string}
     */
    public static function launch(string $source = 'filament', bool $full = false, ?int $userId = null): array
    {
        $run = YoutubeSyncRun::query()->create([
            'status' => YoutubeSyncRun::STATUS_QUEUED,
            'source' => $source,
            'triggered_by_user_id' => $userId,
            'is_dry_run' => false,
            'is_full_sync' => $full,
        ]);

        $queue = (string) Config::get('queue.default', 'sync');

        if ($queue === 'database') {
            SyncYoutubeChannelJob::dispatch($run->id, $full);

            return [
                'ok' => true,
                'run' => $run,
                'message' => 'Synchronisation ajoutée à la file d’attente. Assurez-vous que queue:work tourne.',
            ];
        }

        if (self::startBackgroundArtisan($run->id, $full)) {
            return [
                'ok' => true,
                'run' => $run,
                'message' => 'Synchronisation lancée en arrière-plan. Suivez l’avancement dans « Synchronisations YouTube ».',
            ];
        }

        $run->markFailed(
            'Impossible de lancer un processus en arrière-plan sur ce serveur (exec/proc_open désactivés). '
            .'Configurez QUEUE_CONNECTION=database et lancez php artisan queue:work, '
            .'ou activez le cron HTTP sur « Tâches planifiées ».',
        );

        return [
            'ok' => false,
            'run' => $run->fresh(),
            'message' => (string) $run->error_message,
        ];
    }

    /**
     * Affiche une notification Filament selon le résultat du lancement.
     *
     * @param  array{ok: bool, run: YoutubeSyncRun|null, message: string}  $result
     */
    public static function notifyFilament(array $result): void
    {
        if ($result['ok']) {
            Notification::make()
                ->title('Synchronisation YouTube lancée')
                ->body($result['message'])
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Échec du lancement')
            ->body($result['message'])
            ->danger()
            ->send();
    }

    /**
     * Démarre `php artisan youtube:sync --run-id=…` sans bloquer la requête HTTP.
     */
    private static function startBackgroundArtisan(int $runId, bool $full): bool
    {
        $command = self::buildArtisanCommand($runId, $full);

        if (self::tryProcessFacade($command)) {
            return true;
        }

        if (self::tryExec($command)) {
            return true;
        }

        if (self::tryWindowsPopen($command)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function buildArtisanCommand(int $runId, bool $full): array
    {
        $args = [
            PHP_BINARY,
            base_path('artisan'),
            'youtube:sync',
            '--run-id='.$runId,
        ];

        if ($full) {
            $args[] = '--full';
        }

        return $args;
    }

    /**
     * @param  list<string>  $command
     */
    private static function tryProcessFacade(array $command): bool
    {
        try {
            Process::timeout(null)
                ->path(base_path())
                ->start($command);

            return true;
        } catch (Throwable $throwable) {
            Log::warning('[youtube-sync-launch] Process::start : '.$throwable->getMessage());

            return false;
        }
    }

    /**
     * @param  list<string>  $command
     */
    private static function tryExec(array $command): bool
    {
        if (! function_exists('exec')) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return false;
        }

        $logFile = storage_path('logs/youtube-sync.log');
        $shell = sprintf(
            'nohup %s >> %s 2>&1 &',
            implode(' ', array_map('escapeshellarg', $command)),
            escapeshellarg($logFile),
        );

        try {
            /** @var list<string>|false $output */
            $output = [];
            $exitCode = 0;
            \exec($shell, $output, $exitCode);

            return true;
        } catch (Throwable $throwable) {
            Log::warning('[youtube-sync-launch] exec : '.$throwable->getMessage());

            return false;
        }
    }

    /**
     * @param  list<string>  $command
     */
    private static function tryWindowsPopen(array $command): bool
    {
        if (PHP_OS_FAMILY !== 'Windows' || ! function_exists('popen')) {
            return false;
        }

        $php = $command[0];
        $artisan = $command[1];
        $args = implode(' ', array_slice($command, 2));

        try {
            pclose(popen('start /B "" "'.$php.'" "'.$artisan.'" '.$args, 'r'));

            return true;
        } catch (Throwable $throwable) {
            Log::warning('[youtube-sync-launch] popen : '.$throwable->getMessage());

            return false;
        }
    }
}
