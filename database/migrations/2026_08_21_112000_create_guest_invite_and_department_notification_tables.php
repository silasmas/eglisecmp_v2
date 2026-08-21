<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historique des invitations pasteurs et des notifications départements (accusé de réception).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guest_invite_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guest_pastoral_project_id')->constrained('guest_pastoral_projects')->cascadeOnDelete();
            $table->foreignId('guest_pastor_id')->constrained('guest_pastors')->cascadeOnDelete();
            $table->string('channel', 16);
            $table->string('recipient', 255)->nullable();
            $table->string('status', 24)->default('sent');
            $table->string('message_preview', 500)->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['guest_pastoral_project_id', 'sent_at'], 'guest_invite_proj_sent_idx');
            $table->index(['guest_pastor_id', 'channel'], 'guest_invite_pastor_channel_idx');
        });

        Schema::create('guest_department_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guest_info_submission_id')->constrained('guest_info_submissions')->cascadeOnDelete();
            $table->foreignId('church_department_id')->constrained('church_departments')->cascadeOnDelete();
            $table->string('channel', 16)->default('email');
            $table->string('recipient', 255)->nullable();
            $table->string('status', 24)->default('sent');
            $table->json('meta')->nullable();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by_name', 120)->nullable();
            $table->string('acknowledged_via', 24)->nullable();
            $table->timestamps();

            $table->index(['guest_info_submission_id', 'church_department_id'], 'guest_dept_notif_sub_dept_idx');
            $table->index(['church_department_id', 'acknowledged_at'], 'guest_dept_notif_ack_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_department_notifications');
        Schema::dropIfExists('guest_invite_dispatches');
    }
};
