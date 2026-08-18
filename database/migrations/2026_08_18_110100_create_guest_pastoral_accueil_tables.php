<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projets d’accueil de pasteurs invités (liés à un événement).
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        Schema::create('guest_pastoral_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('event_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('event_id', 'gpp_event_id_fk')->references('id')->on('events')->nullOnDelete();
        });

        Schema::create('guest_pastoral_project_department', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_pastoral_project_id');
            $table->unsignedBigInteger('church_department_id');
            $table->unique(['guest_pastoral_project_id', 'church_department_id'], 'gpp_dept_unique');
            $table->foreign('guest_pastoral_project_id', 'gpp_dept_project_fk')
                ->references('id')->on('guest_pastoral_projects')->cascadeOnDelete();
            $table->foreign('church_department_id', 'gpp_dept_department_fk')
                ->references('id')->on('church_departments')->cascadeOnDelete();
        });

        Schema::create('guest_pastors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('full_name');
            $table->string('church_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('arrival_at')->nullable();
            $table->timestamp('ministry_at')->nullable();
            $table->string('invite_token', 32)->unique();
            $table->timestamp('form_opened_at')->nullable();
            $table->timestamp('form_submitted_at')->nullable();
            $table->timestamps();
            $table->foreign('project_id', 'gp_project_fk')
                ->references('id')->on('guest_pastoral_projects')->cascadeOnDelete();
        });

        Schema::create('guest_info_forms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('is_published')->default(false);
            $table->timestamp('visible_from')->nullable();
            $table->timestamp('visible_until')->nullable();
            $table->string('access_password')->nullable();
            $table->json('design')->nullable();
            $table->text('intro_html')->nullable();
            $table->text('cmp_info_html')->nullable();
            $table->timestamps();
            $table->foreign('project_id', 'gif_project_fk')
                ->references('id')->on('guest_pastoral_projects')->cascadeOnDelete();
        });

        Schema::create('guest_info_form_sections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('department_ids')->nullable();
            $table->timestamps();
            $table->foreign('form_id', 'gifs_form_fk')
                ->references('id')->on('guest_info_forms')->cascadeOnDelete();
        });

        Schema::create('guest_info_form_fields', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('section_id');
            $table->string('key', 64);
            $table->string('label');
            $table->string('type', 32);
            $table->json('options')->nullable();
            $table->json('department_ids')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('help_text')->nullable();
            $table->timestamps();
            $table->unique(['section_id', 'key'], 'giff_section_key_unique');
            $table->foreign('section_id', 'giff_section_fk')
                ->references('id')->on('guest_info_form_sections')->cascadeOnDelete();
        });

        Schema::create('guest_info_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('guest_pastor_id');
            $table->unsignedBigInteger('form_id');
            $table->string('access_token', 48)->unique();
            $table->json('payload');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['guest_pastor_id', 'form_id'], 'gis_pastor_form_unique');
            $table->foreign('guest_pastor_id', 'gis_pastor_fk')
                ->references('id')->on('guest_pastors')->cascadeOnDelete();
            $table->foreign('form_id', 'gis_form_fk')
                ->references('id')->on('guest_info_forms')->cascadeOnDelete();
        });
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_info_submissions');
        Schema::dropIfExists('guest_info_form_fields');
        Schema::dropIfExists('guest_info_form_sections');
        Schema::dropIfExists('guest_info_forms');
        Schema::dropIfExists('guest_pastors');
        Schema::dropIfExists('guest_pastoral_project_department');
        Schema::dropIfExists('guest_pastoral_projects');
    }
};
