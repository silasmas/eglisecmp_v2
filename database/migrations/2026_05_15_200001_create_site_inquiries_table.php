<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formulaires site : requête de prière ou demande de rendez-vous.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 32);
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('message');
            $table->dateTime('preferred_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_inquiries');
    }
};
