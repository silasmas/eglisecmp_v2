<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ChurchDepartmentManager;
use App\Services\ChurchWorkerFromContactService;
use Illuminate\Console\Command;

/**
 * Crée les dossiers ouvriers manquants pour les responsables déjà importés.
 */
final class SyncManagersToWorkersCommand extends Command
{
    protected $signature = 'church:sync-managers-to-workers';

    protected $description = 'Crée / met à jour les dossiers church_workers pour chaque responsable de département';

    public function handle(ChurchWorkerFromContactService $factory): int
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $managers = ChurchDepartmentManager::query()->with('department')->orderBy('id')->get();
        $this->info('Responsables à traiter : '.$managers->count());

        foreach ($managers as $manager) {
            if ($manager->department === null) {
                $skipped++;
                $this->warn('Manager #'.$manager->id.' sans département');

                continue;
            }

            $result = $factory->upsert(
                $manager->department,
                $manager->full_name,
                $manager->phone,
                $manager->email,
                (bool) $manager->is_primary,
            );

            if ($result['worker'] === null) {
                $skipped++;
                $this->warn($result['skipped_reason'] ?? 'Ignoré');

                continue;
            }

            if ($result['created']) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->info(sprintf('Créés : %d · mis à jour : %d · ignorés : %d', $created, $updated, $skipped));

        return self::SUCCESS;
    }
}
