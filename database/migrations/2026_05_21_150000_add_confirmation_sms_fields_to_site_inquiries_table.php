<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi de l'envoi SMS lors de la confirmation d'un rendez-vous pastoral.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->string('confirmation_sms_status', 32)->nullable()->after('appointment_status');
            $table->timestamp('confirmation_sms_sent_at')->nullable()->after('confirmation_sms_status');
            $table->text('confirmation_sms_response')->nullable()->after('confirmation_sms_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmation_sms_status',
                'confirmation_sms_sent_at',
                'confirmation_sms_response',
            ]);
        });
    }
};
