<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute clôture / suspension / chrono / prochain RDV aux dossiers pastoraux.
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->string('dossier_status', 32)->default('open')->after('reception_status');
            $table->timestamp('closed_at')->nullable()->after('completed_at');
            $table->timestamp('suspended_at')->nullable()->after('closed_at');
            $table->timestamp('session_started_at')->nullable()->after('suspended_at');
            $table->unsignedSmallInteger('session_duration_minutes')->nullable()->after('session_started_at');
            $table->boolean('time_respected')->nullable()->after('session_duration_minutes');
            $table->timestamp('next_appointment_at')->nullable()->after('time_respected');
            $table->foreignId('reopened_by')->nullable()->after('next_appointment_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('reopened_by');
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn([
                'dossier_status',
                'closed_at',
                'suspended_at',
                'session_started_at',
                'session_duration_minutes',
                'time_respected',
                'next_appointment_at',
                'reopened_at',
            ]);
        });
    }
};
