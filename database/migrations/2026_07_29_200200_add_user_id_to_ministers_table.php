<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lie un compte admin Filament à une fiche pasteur (réception pastorale).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministers', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ministers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
