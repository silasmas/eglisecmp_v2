<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Exécute et suit les migrations / seeders / synchronisations depuis l’admin.
 *
 * Toute migration individuelle en échec (schéma déjà présent, incohérence legacy,
 * etc.) est marquée comme appliquée et la suite continue — sauf erreur de connexion.
 */
final class DatabaseSyncRunner
{
    private const CACHE_KEY_LAST_RUN = 'cmp.database_sync.last_run';

    /**
     * Seeders sûrs / idempotents pour la sync admin (pas de factories ni import SQL).
     *
     * @var list<class-string>
     */
    private const SAFE_SEEDERS = [
        \Database\Seeders\ChurchDepartmentSeeder::class,
        \Database\Seeders\ChurchCellSeeder::class,
        \Database\Seeders\ChurchExtensionSeeder::class,
        \Database\Seeders\SiteStatisticSeeder::class,
    ];

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
            self::ensureMigrationsTable();
            $ran = collect(DB::table('migrations')->pluck('migration'));
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
            self::ensureMigrationsTable();
            $ranCount = (int) DB::table('migrations')->count();
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
     * Applique les migrations en attente une par une.
     * En cas de « déjà existant », marque la migration et continue.
     *
     * @return array{success: bool, output: string, error: string|null, ran_at: string, source?: string, command?: string}
     */
    public static function migrate(string $source = 'filament'): array
    {
        $ranAt = now()->toIso8601String();
        $log = [];
        $skipped = [];
        $applied = [];

        try {
            self::ensureMigrationsTable();
        } catch (Throwable $throwable) {
            return self::storeResult([
                'success' => false,
                'output' => $throwable->getMessage(),
                'error' => $throwable->getMessage(),
                'ran_at' => $ranAt,
                'source' => $source,
                'command' => 'migrate',
            ]);
        }

        $pending = self::pendingMigrations();

        if ($pending === []) {
            return self::storeResult([
                'success' => true,
                'output' => 'Aucune à jour : aucune migration en attente.',
                'error' => null,
                'ran_at' => $ranAt,
                'source' => $source,
                'command' => 'migrate',
            ]);
        }

        foreach ($pending as $migration) {
            $relativePath = 'database/migrations/'.$migration.'.php';

            if (! File::exists(base_path($relativePath))) {
                $log[] = "[ignore] Fichier introuvable : {$migration}";
                continue;
            }

            try {
                $exitCode = Artisan::call('migrate', [
                    '--force' => true,
                    '--path' => $relativePath,
                    '--no-interaction' => true,
                ]);
                $output = trim(Artisan::output());
            } catch (Throwable $throwable) {
                $exitCode = 1;
                $output = $throwable->getMessage();
            }

            if ($exitCode === 0) {
                $applied[] = $migration;
                $log[] = "[ok] {$migration}".($output !== '' ? "\n{$output}" : '');
                continue;
            }

            // Ne jamais bloquer la sync : une migration déjà appliquée / incohérente
            // est marquée comme faite et on passe à la suivante.
            if (self::isFatalConnectionError($output)) {
                $log[] = "[connexion] {$migration}\n{$output}";

                return self::storeResult([
                    'success' => false,
                    'output' => implode("\n\n", $log),
                    'error' => 'Erreur de connexion à la base. Vérifiez MySQL / .env.',
                    'ran_at' => $ranAt,
                    'source' => $source,
                    'command' => 'migrate',
                ]);
            }

            self::markMigrationAsRan($migration);
            $skipped[] = $migration;
            $reason = self::isAlreadyAppliedSchemaError($output)
                ? 'déjà présent'
                : 'ignoré pour continuer';
            $log[] = "[{$reason} — marqué comme appliqué] {$migration}\n{$output}";
        }

        $summary = sprintf(
            "Synchronisation terminée.\nAppliquées : %d\nIgnorées / déjà présentes : %d\nRestantes : %d",
            count($applied),
            count($skipped),
            count(self::pendingMigrations()),
        );

        return self::storeResult([
            'success' => true,
            'output' => $summary."\n\n".implode("\n\n", $log),
            'error' => null,
            'ran_at' => $ranAt,
            'source' => $source,
            'command' => 'migrate',
        ]);
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
     * Liste des seeders exécutables depuis l’admin.
     *
     * @return list<string>
     */
    public static function safeSeederLabels(): array
    {
        return array_map(
            static fn (string $class): string => class_basename($class),
            self::SAFE_SEEDERS,
        );
    }

    /**
     * Exécute les seeders sûrs un par un (départements, extensions, stats…).
     * Un seeder en échec n’empêche pas les suivants.
     *
     * @param  string  $source  Origine de l’appel (filament, http-deploy…)
     * @return array{success: bool, output: string, error: string|null, ran_at: string, source?: string, command?: string}
     */
    public static function seed(string $source = 'filament'): array
    {
        $ranAt = now()->toIso8601String();
        $log = [];
        $ok = 0;
        $failed = 0;

        foreach (self::SAFE_SEEDERS as $seederClass) {
            $label = class_basename($seederClass);

            try {
                $exitCode = Artisan::call('db:seed', [
                    '--class' => $seederClass,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                $output = trim(Artisan::output());

                if ($exitCode === 0) {
                    $ok++;
                    $log[] = "[ok] {$label}".($output !== '' ? "\n{$output}" : '');
                } else {
                    $failed++;
                    $log[] = "[échec] {$label}\n".($output !== '' ? $output : "Code {$exitCode}");
                }
            } catch (Throwable $throwable) {
                $failed++;
                $log[] = "[échec] {$label}\n".$throwable->getMessage();
            }
        }

        $summary = sprintf(
            "Seeders terminés.\nOK : %d\nÉchecs : %d\nClasses : %s",
            $ok,
            $failed,
            implode(', ', self::safeSeederLabels()),
        );

        return self::storeResult([
            'success' => $failed === 0,
            'output' => $summary."\n\n".implode("\n\n", $log),
            'error' => $failed > 0 ? "{$failed} seeder(s) en échec (voir sortie)." : null,
            'ran_at' => $ranAt,
            'source' => $source,
            'command' => 'db:seed',
        ]);
    }

    /**
     * Indique si l’erreur signifie que le schéma est déjà en place.
     */
    private static function isAlreadyAppliedSchemaError(string $output): bool
    {
        $haystack = strtolower($output);

        return str_contains($haystack, 'already exists')
            || str_contains($haystack, 'duplicate column')
            || str_contains($haystack, 'duplicate key')
            || str_contains($haystack, 'duplicate entry')
            || str_contains($haystack, "doesn't exist")
            || str_contains($haystack, 'does not exist')
            || str_contains($haystack, 'unknown column')
            || str_contains($haystack, 'cannot add')
            || str_contains($haystack, 'can\'t drop')
            || str_contains($haystack, 'check that column/key exists')
            || str_contains($output, '42S01')
            || str_contains($output, '42S02')
            || str_contains($output, '42S21')
            || str_contains($output, '42S22')
            || str_contains($output, '1050')
            || str_contains($output, '1054')
            || str_contains($output, '1060')
            || str_contains($output, '1061')
            || str_contains($output, '1091');
    }

    /**
     * Erreurs fatales (connexion) : on arrête vraiment la sync.
     */
    private static function isFatalConnectionError(string $output): bool
    {
        $haystack = strtolower($output);

        return str_contains($haystack, 'connection refused')
            || str_contains($haystack, 'access denied for user')
            || str_contains($haystack, 'could not find driver')
            || str_contains($haystack, 'no connection could be made')
            || str_contains($haystack, 'sqlstate[hy000] [2002]')
            || str_contains($haystack, 'sqlstate[hy000] [1045]');
    }

    /**
     * Enregistre une migration comme déjà exécutée (sans rejouer le SQL).
     */
    private static function markMigrationAsRan(string $migration): void
    {
        self::ensureMigrationsTable();

        $exists = DB::table('migrations')->where('migration', $migration)->exists();
        if ($exists) {
            return;
        }

        $batch = (int) (DB::table('migrations')->max('batch') ?? 0);

        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => max(1, $batch),
        ]);
    }

    /**
     * Garantit l’existence de la table `migrations`.
     */
    private static function ensureMigrationsTable(): void
    {
        if (Schema::hasTable('migrations')) {
            return;
        }

        Artisan::call('migrate:install');
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

        return self::storeResult($result);
    }

    /**
     * Persiste le résultat de sync pour l’UI admin.
     *
     * @param  array{success: bool, output: string, error: string|null, ran_at: string, source?: string, command?: string}  $result
     * @return array{success: bool, output: string, error: string|null, ran_at: string, source?: string, command?: string}
     */
    private static function storeResult(array $result): array
    {
        Cache::put(self::CACHE_KEY_LAST_RUN, $result, now()->addDays(30));

        return $result;
    }
}
