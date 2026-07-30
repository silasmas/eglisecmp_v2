<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des cellules de maison CMP.
 */
return new class extends Migration
{
    /**
     * Crée la table church_cells.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('church_cells', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('commune');
            $table->string('day')->nullable();
            $table->string('time')->nullable();
            $table->string('host')->nullable();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Supprime la table church_cells.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('church_cells');
    }
};
