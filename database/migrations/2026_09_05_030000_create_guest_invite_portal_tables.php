<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenu portail invité (tenues, liturgie, jours, équipe) + lettre PDF + token portail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_event_outfits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('guest_pastoral_projects')->cascadeOnDelete();
            $table->string('session_key', 32);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order'], 'guest_outfits_project_sort_idx');
        });

        Schema::create('guest_liturgy_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('guest_pastoral_projects')->cascadeOnDelete();
            $table->string('session_key', 32);
            $table->string('title');
            $table->time('starts_at_time')->nullable();
            $table->time('ends_at_time')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order'], 'guest_liturgy_sess_sort_idx');
        });

        Schema::create('guest_liturgy_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('guest_liturgy_sessions')->cascadeOnDelete();
            $table->time('starts_at_time')->nullable();
            $table->time('ends_at_time')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['session_id', 'sort_order'], 'guest_liturgy_items_sort_idx');
        });

        Schema::create('guest_pastor_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guest_pastor_id')->constrained('guest_pastors')->cascadeOnDelete();
            $table->date('day_date');
            $table->string('session_key', 32);
            $table->string('label');
            $table->string('color', 16)->default('#7b1d3e');
            $table->string('location')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['guest_pastor_id', 'day_date'], 'guest_assign_pastor_day_idx');
        });

        Schema::create('guest_pastor_worker', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guest_pastor_id')->constrained('guest_pastors')->cascadeOnDelete();
            $table->foreignId('church_worker_id')->constrained('church_workers')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('church_departments')->nullOnDelete();
            $table->string('display_title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['guest_pastor_id', 'church_worker_id'], 'guest_pastor_worker_unique');
        });

        Schema::create('guest_invitation_letters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained('guest_pastoral_projects')->cascadeOnDelete();
            $table->foreignId('guest_pastor_id')->nullable()->constrained('guest_pastors')->cascadeOnDelete();
            $table->string('recipient_title')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('signature_html')->nullable();
            $table->string('header_image_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'guest_pastor_id'], 'guest_letters_project_pastor_idx');
        });

        Schema::table('guest_info_submissions', function (Blueprint $table): void {
            $table->string('portal_token', 40)->nullable()->unique()->after('access_token');
            $table->timestamp('portal_link_sent_at')->nullable()->after('portal_token');
        });
    }

    public function down(): void
    {
        Schema::table('guest_info_submissions', function (Blueprint $table): void {
            $table->dropColumn(['portal_token', 'portal_link_sent_at']);
        });

        Schema::dropIfExists('guest_invitation_letters');
        Schema::dropIfExists('guest_pastor_worker');
        Schema::dropIfExists('guest_pastor_assignments');
        Schema::dropIfExists('guest_liturgy_items');
        Schema::dropIfExists('guest_liturgy_sessions');
        Schema::dropIfExists('guest_event_outfits');
    }
};
