<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des synchronisations YouTube (succès, échecs, statistiques).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youtube_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 32)->default('queued')->index();
            $table->string('source', 64)->default('command')->index();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('message')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('playlists')->default(0);
            $table->unsignedInteger('videos')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('unchanged_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->boolean('is_dry_run')->default(false);
            $table->boolean('is_full_sync')->default(false);
            $table->longText('output_log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youtube_sync_runs');
    }
};
