<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versets du jour programmés (visibilité 24 h à partir de publish_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_verses', function (Blueprint $table) {
            $table->id();
            $table->dateTime('publish_at');
            $table->dateTime('visible_until');
            $table->json('reference');
            $table->json('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'publish_at', 'visible_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_verses');
    }
};
