<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Étend les réglages admin du mur de témoignages (photos, vidéo, anonymat, noms).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimony_wall_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('max_photos_per_testimony')->default(5)->after('allow_photo_upload');
            $table->boolean('allow_youtube_link')->default(true)->after('max_photos_per_testimony');
            $table->boolean('allow_video_upload')->default(true)->after('allow_youtube_link');
            $table->unsignedSmallInteger('max_video_upload_mb')->default(5)->after('allow_video_upload');
            $table->boolean('allow_anonymous')->default(true)->after('max_video_upload_mb');
            $table->boolean('require_first_name')->default(true)->after('allow_anonymous');
            $table->boolean('require_last_name')->default(false)->after('require_first_name');
        });
    }

    public function down(): void
    {
        Schema::table('testimony_wall_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'max_photos_per_testimony',
                'allow_youtube_link',
                'allow_video_upload',
                'max_video_upload_mb',
                'allow_anonymous',
                'require_first_name',
                'require_last_name',
            ]);
        });
    }
};
