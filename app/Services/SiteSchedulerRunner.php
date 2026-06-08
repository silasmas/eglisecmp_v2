<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Exécute le scheduler Laravel et mémorise le résultat pour l’admin Filament / cron HTTP.
 */
final class SiteSchedulerRunner
{
    /**
     * Lance `php artisan schedule:run` et enregistre le statut.
     *
     * @param  string  $source  Origine : manual, http, filament.
     * @return array{success: bool, exit_code: int, output: string, source: string, ran_at: string, queue_connection: string, error: string|null}
     */
    public static function run(string $source = 'manual'): array
    {
        $ranAt = now();

        try {
            $exitCode = Artisan::call('schedule:run');
            $output = trim(Artisan::output());

            $payload = self::buildPayload(
                success: $exitCode === 0,
                exitCode: $exitCode,
                output: $output,
                source: $source,
                ranAt: $ranAt,
                error: null,
            );

            Cache::put(self::lastRunCacheKey(), $payload, now()->addDays(30));

            return $payload;
        } catch (Throwable $throwable) {
            $payload = self::buildPayload(
                success: false,
                exitCode: 1,
                output: '',
                source: $source,
                ranAt: $ranAt,
                error: $throwable->getMessage(),
            );

            Cache::put(self::lastRunCacheKey(), $payload, now()->addDays(30));

            return $payload;
        }
    }

    /**
     * Exécute une commande planifiée isolée (test depuis l’admin).
     *
     * @param  string  $command  Nom artisan (ex. youtube:check-live).
     * @return array{success: bool, exit_code: int, output: string, command: string, ran_at: string, error: string|null}
     */
    public static function runCommand(string $command): array
    {
        $allowed = collect((array) config('site_scheduler.tasks', []))
            ->pluck('command')
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->all();

        if (! in_array($command, $allowed, true)) {
            return [
                'success' => false,
                'exit_code' => 1,
                'output' => '',
                'command' => $command,
                'ran_at' => now()->toIso8601String(),
                'error' => 'Commande non autorisée.',
            ];
        }

        $ranAt = now();

        try {
            $exitCode = Artisan::call($command);
            $output = trim(Artisan::output());

            return [
                'success' => $exitCode === 0,
                'exit_code' => $exitCode,
                'output' => $output,
                'command' => $command,
                'ran_at' => $ranAt->toIso8601String(),
                'error' => null,
            ];
        } catch (Throwable $throwable) {
            return [
                'success' => false,
                'exit_code' => 1,
                'output' => '',
                'command' => $command,
                'ran_at' => $ranAt->toIso8601String(),
                'error' => $throwable->getMessage(),
            ];
        }
    }

    /**
     * Indique si le cron HTTP (URL /deploy/scheduler) est activé.
     */
    public static function isHttpCronEnabled(): bool
    {
        return (bool) Cache::get(self::httpEnabledCacheKey(), false);
    }

    /**
     * Active ou désactive le cron HTTP.
     */
    public static function setHttpCronEnabled(bool $enabled): void
    {
        Cache::forever(self::httpEnabledCacheKey(), $enabled);
    }

    /**
     * Dernière exécution enregistrée.
     *
     * @return array<string, mixed>|null
     */
    public static function getLastRun(): ?array
    {
        $payload = Cache::get(self::lastRunCacheKey());

        return is_array($payload) ? $payload : null;
    }

    /**
     * État global pour le tableau de bord admin.
     *
     * @return array<string, mixed>
     */
    public static function status(): array
    {
        $lastRun = self::getLastRun();
        $httpEnabled = self::isHttpCronEnabled();
        $lastRunAt = is_array($lastRun) && is_string($lastRun['ran_at'] ?? null)
            ? Carbon::parse($lastRun['ran_at'])
            : null;

        $minutesSinceLastRun = $lastRunAt instanceof Carbon
            ? (int) $lastRunAt->diffInMinutes(now())
            : null;

        $isHealthy = ! $httpEnabled
            || (
                is_array($lastRun)
                && ($lastRun['success'] ?? false) === true
                && $minutesSinceLastRun !== null
                && $minutesSinceLastRun <= 5
            );

        return [
            'http_enabled' => $httpEnabled,
            'last_run' => $lastRun,
            'minutes_since_last_run' => $minutesSinceLastRun,
            'is_healthy' => $isHealthy,
            'queue_connection' => (string) config('queue.default', 'sync'),
            'pending_queue_jobs' => self::countPendingFileQueueJobs(),
            'tasks' => config('site_scheduler.tasks', []),
            'http_url' => self::httpCronUrl(),
        ];
    }

    /**
     * URL publique du cron HTTP (si DEPLOY_TOKEN configuré).
     */
    public static function httpCronUrl(): ?string
    {
        $token = (string) config('app.deploy_token', '');

        if ($token === '') {
            return null;
        }

        return url('/deploy/scheduler/'.$token);
    }

    /**
     * Compte les jobs en attente lorsque QUEUE_CONNECTION=file.
     */
    public static function countPendingFileQueueJobs(): int
    {
        if ((string) config('queue.default') !== 'file') {
            return 0;
        }

        $path = storage_path('framework/queues');

        if (! File::isDirectory($path)) {
            return 0;
        }

        $count = 0;

        foreach (File::allFiles($path) as $file) {
            if ($file->getExtension() === 'json' || str_ends_with($file->getFilename(), '.json')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{success: bool, exit_code: int, output: string, source: string, ran_at: string, queue_connection: string, error: string|null}
     */
    private static function buildPayload(
        bool $success,
        int $exitCode,
        string $output,
        string $source,
        Carbon $ranAt,
        ?string $error,
    ): array {
        return [
            'success' => $success,
            'exit_code' => $exitCode,
            'output' => $output,
            'source' => $source,
            'ran_at' => $ranAt->toIso8601String(),
            'queue_connection' => (string) config('queue.default', 'sync'),
            'error' => $error,
        ];
    }

    private static function lastRunCacheKey(): string
    {
        return (string) config('site_scheduler.cache.last_run', 'site_scheduler.last_run');
    }

    private static function httpEnabledCacheKey(): string
    {
        return (string) config('site_scheduler.cache.http_enabled', 'site_scheduler.http_enabled');
    }
}
