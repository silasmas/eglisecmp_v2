<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Complète les libellés des rôles d’intercession affichés dans l’admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'intercession')
            ->whereNull('display_name')
            ->update(['display_name' => 'Intercession']);

        DB::table('roles')
            ->where('name', 'Priere')
            ->whereNull('display_name')
            ->update(['display_name' => 'Prière']);
    }

    public function down(): void
    {
        //
    }
};
