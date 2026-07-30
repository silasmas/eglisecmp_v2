<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rapports de présence aux cultes (équipe protocole).
 */
return new class extends Migration
{
    /**
     * Crée worship_service_reports.
     */
    public function up(): void
    {
        Schema::create('worship_service_reports', function (Blueprint $table): void {
            $table->id();
            $table->date('service_date');
            $table->string('service_type', 64);
            $table->unsignedInteger('attendees_count');
            $table->text('report_text');
            $table->string('submitted_by')->nullable();
            $table->string('phone', 32)->nullable();
            $table->timestamps();

            $table->index(['service_date', 'service_type']);
            $table->index('service_type');
        });
    }

    /**
     * Supprime worship_service_reports.
     */
    public function down(): void
    {
        Schema::dropIfExists('worship_service_reports');
    }
};
