<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * Ajoute le rôle « priere » pour les notifications de requêtes de prière.
 */
return new class extends Migration
{
    public function up(): void
    {
        Role::query()->firstOrCreate(
            ['name' => 'priere', 'guard_name' => 'web'],
            ['display_name' => 'Prière'],
        );
    }

    public function down(): void
    {
        //
    }
};
