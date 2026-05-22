<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suivi de la notification e-mail envoyée à l’équipe d’intercession.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->string('prayer_team_notification_status', 32)->nullable()->after('is_anonymous');
            $table->timestamp('prayer_team_notified_at')->nullable()->after('prayer_team_notification_status');
            $table->text('prayer_team_notification_response')->nullable()->after('prayer_team_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->dropColumn([
                'prayer_team_notification_status',
                'prayer_team_notified_at',
                'prayer_team_notification_response',
            ]);
        });
    }
};
