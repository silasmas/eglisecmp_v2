<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Projets et sous-projets.
        Schema::create('projects', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->string('designation', 191)->nullable();
            $table->text('observation')->nullable();
            $table->integer('is_active')->nullable();
            $table->timestamps();
        });

        Schema::create('subprojects', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->string('designation', 191)->nullable();
            $table->text('description')->nullable();
            $table->text('guide')->nullable();
            $table->integer('project_id')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->string('designation', 191)->nullable();
            $table->text('description')->nullable();
            $table->integer('entity_id')->nullable();
            $table->integer('is_active')->default(1);
            $table->string('image_url', 191)->nullable();
            $table->timestamps();
        });

        // Publications.
        Schema::create('posts', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->text('title')->nullable();
            $table->integer('type')->nullable();
            $table->string('link_url', 191)->nullable();
            $table->text('image_url')->nullable();
            $table->text('body')->nullable();
            $table->string('author', 191)->nullable();
            $table->text('observation')->nullable();
            $table->integer('event_id')->nullable();
            $table->string('slug', 200)->nullable();
            $table->integer('is_active')->default(1);
            $table->text('references')->nullable();
            $table->dateTime('date_publication')->nullable();
            $table->text('fichier_url')->nullable();
            $table->integer('minister_id')->nullable();
            $table->timestamps();
        });

        // Temoignages: ancien format + nouveau format.
        Schema::create('testimonials', function (Blueprint $table) {
            $table->unsignedInteger('id', true);
            $table->string('fullname', 191)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('type', 50)->nullable();
            $table->text('body')->nullable();
            $table->string('image_url', 191)->nullable();
            $table->integer('is_active')->nullable();
            $table->timestamps();
        });

        Schema::create('testimonies', function (Blueprint $table) {
            $table->id();
            $table->enum('kind', ['text', 'video', 'mix'])->default('text');
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('title');
            $table->text('text')->nullable();
            $table->string('video')->nullable();
            $table->string('postit_color', 20)->nullable();
            $table->string('font_family', 100)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->enum('verification_type', ['email', 'phone', 'both'])->default('email');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });

        Schema::create('testimony_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('testimony_id')->constrained('testimonies')->cascadeOnDelete();
            $table->string('image');
            $table->timestamps();
        });

        // Paiements/offrandes.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('provider_reference')->nullable();
            $table->string('order_number')->nullable();
            $table->string('amount_customer')->nullable();
            $table->string('phone')->nullable();
            $table->string('currency')->nullable();
            $table->double('montant')->nullable();
            $table->string('chanel')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('offrande_id')->constrained('offrandes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('fullname', 250)->nullable();
            $table->string('numberPhone', 250)->nullable();
            $table->string('pays', 250)->nullable();
            $table->string('type', 250)->nullable();
            $table->string('etat')->default('0');
            $table->timestamps();
        });

        // Requetes / formulaires.
        Schema::create('requetes', function (Blueprint $table) {
            $table->id();
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('pays', 50)->nullable();
            $table->text('requete');
            $table->timestamps();
        });

        // Videos (on garde les noms historiques, ex: Type, dateRealiser).
        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->text('video')->nullable();
            $table->text('titre')->nullable();
            $table->text('description')->nullable();
            $table->string('Type', 100)->nullable();
            $table->text('jour')->nullable();
            $table->integer('is_active')->nullable();
            $table->dateTime('dateRealiser')->nullable();
            $table->string('imag_url', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('bureaus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('reception_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('minister_id');
            $table->unsignedBigInteger('bureau_id');
            $table->string('day_of_week');
            $table->string('time_slot');
            $table->timestamps();
            $table->index('minister_id');
            $table->index('bureau_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_schedules');
        Schema::dropIfExists('bureaus');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('requetes');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('testimony_images');
        Schema::dropIfExists('testimonies');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('subprojects');
        Schema::dropIfExists('projects');
    }
};
