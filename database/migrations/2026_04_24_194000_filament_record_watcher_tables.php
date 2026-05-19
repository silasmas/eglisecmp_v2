<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $watches = config('filament-record-watcher.table_name', 'watches');
        $events = config('filament-record-watcher.events_table_name', 'watch_events');

        if (! Schema::hasTable($watches)) {
            Schema::create($watches, function (Blueprint $table) {
                $table->id();
                $table->morphs('watchable');
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->json('conditions')->nullable();
                $table->timestamp('paused_at')->nullable();
                $table->timestamps();

                $table->unique(['watchable_type', 'watchable_id', 'user_id'], 'watches_unique_subscription');
                $table->index(['user_id', 'paused_at']);
            });
        }

        if (! Schema::hasTable($events)) {
            Schema::create($events, function (Blueprint $table) use ($watches) {
                $table->id();
                $table->foreignId('watch_id')
                    ->constrained($watches)
                    ->cascadeOnDelete();
                $table->nullableMorphs('actor');
                $table->json('diff');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['watch_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        $watches = config('filament-record-watcher.table_name', 'watches');
        $events = config('filament-record-watcher.events_table_name', 'watch_events');

        Schema::dropIfExists($events);
        Schema::dropIfExists($watches);
    }
};
