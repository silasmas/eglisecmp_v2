<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vignette / bannière pour la lecture du jour et les programmes (hero + modales).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_verses', function (Blueprint $table) {
            $table->json('image_url')->nullable()->after('body');
        });

        Schema::table('schedule_programs', function (Blueprint $table) {
            $table->json('image_url')->nullable()->after('description');
            $table->json('banner_image')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('daily_verses', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });

        Schema::table('schedule_programs', function (Blueprint $table) {
            $table->dropColumn(['image_url', 'banner_image']);
        });
    }
};
