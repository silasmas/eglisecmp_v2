<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Coordonnées de contact pour les départements (accueil pasteurs invités).
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::table('church_departments', function (Blueprint $table): void {
            $table->string('contact_phone', 40)->nullable()->after('manager_user_id');
            $table->string('contact_email', 120)->nullable()->after('contact_phone');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('church_departments', function (Blueprint $table): void {
            $table->dropColumn(['contact_phone', 'contact_email']);
        });
    }
};
