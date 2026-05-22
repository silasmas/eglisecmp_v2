<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lie chaque rendez-vous au bureau de réception correspondant au créneau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->unsignedBigInteger('bureau_id')->nullable()->after('minister_id');

            $table->foreign('bureau_id')
                ->references('id')
                ->on('bureaus')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->dropForeign(['bureau_id']);
            $table->dropColumn('bureau_id');
        });
    }
};
