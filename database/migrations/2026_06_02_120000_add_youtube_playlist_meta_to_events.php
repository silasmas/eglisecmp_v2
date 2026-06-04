<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Métadonnées YouTube pour aligner playlists site / chaîne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->unsignedInteger('youtube_playlist_item_count')->nullable()->after('youtube_playlist_id');
            $table->timestamp('youtube_published_at')->nullable()->after('youtube_playlist_item_count');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['youtube_playlist_item_count', 'youtube_published_at']);
        });
    }
};
