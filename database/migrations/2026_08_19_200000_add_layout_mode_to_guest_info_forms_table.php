<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mode d’affichage public : page unique ou assistant (wizard) par rubrique.
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::table('guest_info_forms', function (Blueprint $table): void {
            $table->string('layout_mode', 16)->default('single')->after('is_published');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('guest_info_forms', function (Blueprint $table): void {
            $table->dropColumn('layout_mode');
        });
    }
};
