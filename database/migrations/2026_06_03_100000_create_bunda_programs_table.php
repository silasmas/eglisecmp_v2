<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenus spécifiques Bunda 21 (éditions, plan alimentaire, annonces).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bunda_programs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('edition_year')->unique();
            $table->json('title');
            $table->json('subtitle')->nullable();
            $table->json('description')->nullable();
            $table->json('body')->nullable();
            $table->json('hero_image')->nullable();
            $table->string('meal_plan_path', 500)->nullable();
            $table->string('meal_plan_label', 120)->default('Plan alimentaire');
            $table->unsignedInteger('event_id')->nullable();
            $table->boolean('is_upcoming_announcement')->default(false);
            $table->string('upcoming_month_label', 40)->default('Novembre');
            $table->json('upcoming_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bunda_programs');
    }
};
