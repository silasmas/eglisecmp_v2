<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le slug de sous-menu pour synchroniser les événements avec la navigation.
 */
return new class extends Migration
{
    /**
     * Ajoute menu_slug sur events.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->string('menu_slug', 64)->nullable()->after('theme');
            $table->index('menu_slug');
        });
    }

    /**
     * Retire menu_slug.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropIndex(['menu_slug']);
            $table->dropColumn('menu_slug');
        });
    }
};
