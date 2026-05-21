<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la fenêtre de programmation pour la mise en avant des événements.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dateTime('featured_from')->nullable()->after('est_a_la_une');
            $table->dateTime('featured_until')->nullable()->after('featured_from');
        });
    }

    /**
     * Supprime les colonnes de programmation de mise en avant.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['featured_from', 'featured_until']);
        });
    }
};
