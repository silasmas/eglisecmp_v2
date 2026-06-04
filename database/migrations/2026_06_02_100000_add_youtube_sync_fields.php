<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs de liaison et synchronisation YouTube (posts / événements-playlists).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('youtube_video_id', 20)->nullable()->after('link_url');
            $table->string('youtube_kind', 20)->nullable()->after('youtube_video_id');
            $table->string('youtube_playlist_id', 64)->nullable()->after('youtube_kind');
            $table->timestamp('youtube_synced_at')->nullable()->after('youtube_playlist_id');

            $table->unique('youtube_video_id');
            $table->index('youtube_playlist_id');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->string('youtube_playlist_id', 64)->nullable()->after('image_url');
            $table->unique('youtube_playlist_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropUnique(['youtube_playlist_id']);
            $table->dropColumn('youtube_playlist_id');
        });

        Schema::table('posts', function (Blueprint $table): void {
            $table->dropUnique(['youtube_video_id']);
            $table->dropColumn([
                'youtube_video_id',
                'youtube_kind',
                'youtube_playlist_id',
                'youtube_synced_at',
            ]);
        });
    }
};
