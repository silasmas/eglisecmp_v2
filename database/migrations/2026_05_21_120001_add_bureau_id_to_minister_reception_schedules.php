<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lie chaque plage de réception pasteur à un bureau (unicité par bureau + créneau).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minister_reception_schedules', function (Blueprint $table): void {
            $table->unsignedBigInteger('bureau_id')->nullable()->after('minister_id');
            $table->foreign('bureau_id')
                ->references('id')
                ->on('bureaus')
                ->nullOnDelete();
            $table->index(['bureau_id', 'day_of_week', 'is_active'], 'mrs_bureau_day_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('minister_reception_schedules', function (Blueprint $table): void {
            $table->dropForeign(['bureau_id']);
            $table->dropIndex('mrs_bureau_day_active_idx');
            $table->dropColumn('bureau_id');
        });
    }
};
