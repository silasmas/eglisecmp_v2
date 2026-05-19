<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programmes affichés sur le site (culte du jour, hebdo, séminaire, live, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_programs', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 32);
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('day_label', 120)->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->string('time_label', 120)->nullable();
            $table->unsignedTinyInteger('live_hour')->nullable();
            $table->unsignedTinyInteger('live_minute')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->unsignedInteger('event_id')->nullable();
            $table->string('icon_key', 64)->default('book-open');
            $table->boolean('grid_wide')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['kind', 'is_active', 'sort_order']);
            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_programs');
    }
};
