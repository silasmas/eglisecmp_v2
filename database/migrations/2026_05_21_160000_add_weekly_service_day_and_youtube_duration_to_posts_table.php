<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le jour du culte hebdomadaire facultatif et la durée vidéo (YouTube API, secondes).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('weekly_service_day', 20)->nullable();
            $table->unsignedInteger('youtube_duration_seconds')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['weekly_service_day', 'youtube_duration_seconds']);
        });
    }
};
