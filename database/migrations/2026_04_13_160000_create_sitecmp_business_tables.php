<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Actualites publiees sur la plateforme.
        Schema::create('actualites', function (Blueprint $table) {
            $table->increments('id');
            $table->text('description')->nullable();
            $table->text('titre')->nullable();
            $table->string('soutTitre')->nullable();
            $table->text('img_url')->nullable();
            $table->text('pdf')->nullable();
            $table->integer('is_active')->nullable();
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('expire_at')->nullable();
            $table->timestamps();
        });

        // Entites/structures de l'eglise.
        Schema::create('entities', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->text('designation')->nullable();
            $table->integer('type')->nullable();
            $table->text('titulaire')->nullable();
            $table->text('adresse')->nullable();
            $table->text('image_url')->nullable();
            $table->string('link_facebook', 191)->nullable();
            $table->string('link_instagram', 191)->nullable();
            $table->string('link_twitter', 191)->nullable();
            $table->string('website', 191)->nullable();
            $table->integer('minister_id')->nullable();
            $table->integer('is_active')->default(1);
            $table->text('contact')->nullable();
            $table->timestamps();
        });

        // Evenements.
        Schema::create('events', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->text('designation')->nullable();
            $table->integer('type')->nullable();
            $table->text('lieu')->nullable();
            $table->string('orateur', 50)->nullable();
            $table->dateTime('date_debut')->nullable();
            $table->dateTime('date_fin')->nullable();
            $table->integer('is_active')->default(1);
            $table->text('theme')->nullable();
            $table->text('references')->nullable();
            $table->text('image_url')->nullable();
            $table->integer('est_a_la_une')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('faithfuls', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->text('fullname')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->string('commune', 191)->nullable();
            $table->string('adresse', 191)->nullable();
            $table->integer('est_membre')->nullable();
            $table->integer('is_active')->default(1);
            $table->string('ville', 191)->nullable();
            $table->string('pays', 191)->nullable();
            $table->timestamps();
        });

        Schema::create('galleries', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->text('image_url')->nullable();
            $table->text('description')->nullable();
            $table->integer('is_active')->nullable();
            $table->integer('post_id')->nullable();
            $table->integer('project_id')->nullable();
            $table->timestamps();
        });

        // Configuration globale (avec champs JSON dans le SQL source).
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->nullable();
            $table->text('site_description')->nullable();
            $table->string('site_logo')->nullable();
            $table->string('site_favicon')->nullable();
            $table->string('theme_color')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('google_analytics_id')->nullable();
            $table->string('posthog_html_snippet')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->json('seo_metadata')->nullable();
            $table->json('email_settings')->nullable();
            $table->string('email_from_address')->nullable();
            $table->string('email_from_name')->nullable();
            $table->json('social_network')->nullable();
            $table->json('more_configs')->nullable();
            $table->timestamps();
        });

        Schema::create('goods', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->text('designation')->nullable();
            $table->text('image_url')->nullable();
            $table->string('price', 191)->nullable();
            $table->text('description')->nullable();
            $table->integer('project_id')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('sujet')->nullable();
            $table->longText('message');
            $table->timestamps();
        });

        Schema::create('ministers', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->text('fullname')->nullable();
            $table->text('image_url')->nullable();
            $table->text('bio')->nullable();
            $table->integer('is_titular')->nullable();
            $table->integer('is_active')->default(1);
            $table->string('contact', 191)->nullable();
            $table->string('type', 200)->nullable();
            $table->string('facebook_url', 191)->nullable();
            $table->string('instagram_url', 191)->nullable();
            $table->string('twitter_url', 191)->nullable();
            $table->string('youtube_url', 191)->nullable();
            $table->timestamps();
        });

        // Formulaire missionnaire.
        Schema::create('missionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->date('birthday');
            $table->string('age')->nullable();
            $table->text('adresse')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->unique();
            $table->string('etat_civil')->nullable();
            $table->string('profession')->nullable();
            $table->string('annee_conversion')->nullable();
            $table->string('lieu_bapteme')->nullable();
            $table->date('date_bapteme')->nullable();
            $table->string('eglise_attache')->default('CMP');
            $table->string('temps_au_cmp')->nullable();
            $table->string('departement')->nullable();
            $table->string('participer_mission')->default('0');
            $table->text('description_mission')->nullable();
            $table->string('formation_biblique')->default('0');
            $table->string('niveau')->default('0');
            $table->string('lecture_bible')->nullable();
            $table->string('livre_bible')->nullable();
            $table->string('base_mission')->nullable();
            $table->string('concepte_familier')->nullable();
            $table->string('langue_fr')->nullable();
            $table->string('langue_en')->nullable();
            $table->string('autres')->nullable();
            $table->text('competence')->nullable();
            $table->string('outils_rs')->default('0');
            $table->text('but_formation')->nullable();
            $table->text('objectif')->nullable();
            $table->string('disponible')->default('0');
            $table->string('validationFormulaire')->default('0');
            $table->string('note_validation')->nullable();
            $table->timestamps();
        });

        Schema::create('newletters', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->string('email', 191)->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('offrandes', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->nullable();
            $table->string('description')->nullable();
            $table->integer('is_active')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offrandes');
        Schema::dropIfExists('newletters');
        Schema::dropIfExists('missionnaires');
        Schema::dropIfExists('ministers');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('goods');
        Schema::dropIfExists('general_settings');
        Schema::dropIfExists('galleries');
        Schema::dropIfExists('faithfuls');
        Schema::dropIfExists('events');
        Schema::dropIfExists('entities');
        Schema::dropIfExists('actualites');
    }
};
