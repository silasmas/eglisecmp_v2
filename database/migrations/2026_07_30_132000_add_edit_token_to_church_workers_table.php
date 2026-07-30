<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeton public pour modification du dossier ouvrier (lien renvoyé au fidèle).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('church_workers', function (Blueprint $table): void {
            $table->uuid('edit_token')->nullable()->unique()->after('badge_generated_at');
            $table->timestamp('edit_token_expires_at')->nullable()->after('edit_token');
        });
    }

    public function down(): void
    {
        Schema::table('church_workers', function (Blueprint $table): void {
            $table->dropColumn(['edit_token', 'edit_token_expires_at']);
        });
    }
};
