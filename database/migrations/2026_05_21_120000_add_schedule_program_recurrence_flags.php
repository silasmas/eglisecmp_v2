<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Options de récurrence, live et visibilité hero pour les programmes site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_programs', function (Blueprint $table): void {
            $table->boolean('is_recurring')->default(true)->after('is_active');
            $table->boolean('streams_live')->default(false)->after('is_recurring');
            $table->boolean('show_in_hero_strip')->default(true)->after('streams_live');
            $table->boolean('suppress_if_event_this_week')->default(true)->after('show_in_hero_strip');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_programs', function (Blueprint $table): void {
            $table->dropColumn([
                'is_recurring',
                'streams_live',
                'show_in_hero_strip',
                'suppress_if_event_this_week',
            ]);
        });
    }
};
