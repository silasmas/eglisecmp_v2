<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo du pasteur invité (mails, formulaire public, réponses).
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::table('guest_pastors', function (Blueprint $table): void {
            $table->string('photo_path')->nullable()->after('full_name');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('guest_pastors', function (Blueprint $table): void {
            $table->dropColumn('photo_path');
        });
    }
};
