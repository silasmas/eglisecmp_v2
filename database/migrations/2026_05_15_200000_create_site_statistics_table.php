<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indicateurs « En chiffres » sur la page d’accueil (SPA), éditables en administration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_statistics', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('icon_key', 40);
            $table->string('label');
            $table->unsignedInteger('numeric_value')->default(0);
            $table->string('suffix', 16)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_statistics');
    }
};
