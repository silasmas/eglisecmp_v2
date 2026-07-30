<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OTP e-mail (ex. inscription ouvrier).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('purpose', 64);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['email', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
