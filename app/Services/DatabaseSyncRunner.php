<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Exécute et suit les migrations / synchronisations de schéma depuis l’admin.
 */
final class DatabaseSyncRunner
{
    private const CACHE_KEY_LAST_RUN = 'cmp.database_sync.last_run';

    /**
     * Liste les migrations en attente (fichiers non encore exécutés).
     *
     * @return list<string>
     */
    public static function pendingMigrations(): array
    {
        $files = File::glob(database_path('migrations/*.php')) ?: [];
        $fileNames = collect($files)
            ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME))
            ->sort()
            ->values();

        try {
            $ran = collect(\Illuminate\Support\Facades\DB::table('migrations')->pluck('migration'));
        } catch (Throwable) {
            return $fileNames->all();
        }

        return $fileNames
            ->reject(fn (string $name): bool => $ran->contains($name))
            ->values()
            ->all();
    }

    /**
     * Statut synthétique pour l’affichage admin.
     *
     * @return array{
     *   pending_count: int,
     *   pending: list<string>,
     *   ran_count: int,
     *   last_run: array<string, mixed>|null,
     *   migration_files_count: int
     * }
     */
    public static function status(): array
    {
        $pending = self::pendingMigrations();
        $files = File::glob(database_path('migrations/*.php')) ?: [];

        $lastRun = Cache::get(self::CACHE_KEY_LAST_RUN);
        if (! is_array($lastRun)) {
            $lastRun = null;
        }

        $ranCount = 0;
        try {
            $ranCount = (int) \Illuminate\Support\Facades\DB::table('migrations')->count();
        } catch (Throwable) {
            $ranCount = 0;
        }

        return [
            'pending_count' => count($pending),
            'pending' => $pending,
            'ran_count' => $ranCount,
            'last_run' => $lastRun,
            'migration_files_count' => count($files),
        ];
    }

    /**
     * Lance `migrate --force` et enregistre le résultat.
     *
     * @return array{success: bool, output: string, error: string|null, ran_at: string}
     */
    public static function migrate(string $source = 'filament'): array
    {
        return self::runArtisan('migrate', ['--force' => true], $source, 'migrate');
    }

    /**
     * Relance le statut détaillé des migrations.
     *
     * @return array{success: bool, output: string, error: string|null, ran_at: string}
     */
    public static function migrateStatus(string $source = 'filament'): array
    {
        return self::runArtisan('migrate:status', [], $source, 'migrate:status');
    }

    /**
     * Régénère les permissions Shield (utile après nouveaux modules).
     *
     * @return array{success: bool, output: string, error: string|null, ran_at: string}
     */
    public static function syncShield(string $source = 'filament'): array
    {
        return self::runArtisan('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--no-interaction' => true,
        ], $source, 'shield:generate');
    }

    /**
     * Exécute une commande Artisan et mémorise le résultat.
     *
     * @param  array<string, mixed>  $parameters
     * @return array{success: bool, output: string, error: string|null, ran_at: string}
     */
    private static function runArtisan(string $command, array $parameters, string $source, string $label): array
    {
        $ranAt = now()->toIso8601String();
        $result = [
            'success' => false,
            'output' => '',
            'error' => null,
            'ran_at' => $ranAt,
            'source' => $source,
            'command' => $label,
        ];

        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = trim(Artisan::output());
            $result['output'] = $output !== '' ? $output : 'Terminé sans sortie.';
            $result['success'] = $exitCode === 0;

            if ($exitCode !== 0) {
                $result['error'] = 'Code de sortie '.$exitCode;
            }
        } catch (Throwable $throwable) {
            $result['error'] = $throwable->getMessage();
            $result['output'] = $throwable->getMessage();
        }

        Cache::put(self::CACHE_KEY_LAST_RUN, $result, now()->addDays(30));

        return $result;
    }
}
