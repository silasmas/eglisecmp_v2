<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lie un rendez-vous à un pasteur et suit la confirmation côté admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->unsignedInteger('minister_id')->nullable()->after('kind');
            $table->string('appointment_status', 32)->default('pending')->after('preferred_at');

            $table->foreign('minister_id')
                ->references('id')
                ->on('ministers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->dropForeign(['minister_id']);
            $table->dropColumn(['minister_id', 'appointment_status']);
        });
    }
};
