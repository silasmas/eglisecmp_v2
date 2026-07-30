<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des extensions CMP (localisation mondiale + dirigeant pastoral).
 */
return new class extends Migration
{
    /**
     * Crée la table church_extensions.
     */
    public function up(): void
    {
        Schema::create('church_extensions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('country');
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('leader_name')->nullable();
            $table->string('leader_photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Supprime la table church_extensions.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_extensions');
    }
};
