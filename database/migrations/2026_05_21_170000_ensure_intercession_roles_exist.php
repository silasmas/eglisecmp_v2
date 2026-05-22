<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Crée les rôles utilisés pour notifier l’équipe d’intercession.
 */
return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'intercession' => 'Intercession',
            'Priere' => 'Prière',
        ];

        foreach ($roles as $name => $displayName) {
            Role::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName],
            );
        }
    }

    public function down(): void
    {
        // Les rôles peuvent être réutilisés ailleurs : pas de suppression automatique.
    }
};
