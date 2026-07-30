<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numéros autorisés à soumettre les rapports de culte (équipe protocole).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_reporters', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 32)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_reporters');
    }
};
