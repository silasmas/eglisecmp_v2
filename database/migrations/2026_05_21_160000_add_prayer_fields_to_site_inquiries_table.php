<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs supplémentaires pour les requêtes de prière (pays, anonymat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->string('country', 190)->nullable()->after('phone');
            $table->boolean('is_anonymous')->default(false)->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->dropColumn(['country', 'is_anonymous']);
        });
    }
};
