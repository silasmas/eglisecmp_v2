<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horaires de réception des pasteurs pour les rendez-vous publics.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minister_reception_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('minister_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('slot_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('minister_id')
                ->references('id')
                ->on('ministers')
                ->cascadeOnDelete();
            $table->index(['minister_id', 'day_of_week', 'is_active'], 'mrs_minister_day_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minister_reception_schedules');
    }
};
