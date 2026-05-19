<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réactions anonymes (clé composite type:id + visiteur + emoji).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reactions', function (Blueprint $table) {
            $table->id();
            $table->string('reactable_key', 80);
            $table->string('reaction_key', 32);
            $table->uuid('visitor_token');
            $table->timestamps();

            $table->unique(['reactable_key', 'reaction_key', 'visitor_token'], 'content_reactions_visitor_unique');
            $table->index('reactable_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reactions');
    }
};
