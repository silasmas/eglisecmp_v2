<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mise en avant programmable des posts sur la page d'accueil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('featured_on_home')->default(false)->after('is_active');
            $table->dateTime('featured_from')->nullable()->after('featured_on_home');
            $table->dateTime('featured_until')->nullable()->after('featured_from');
            $table->unsignedSmallInteger('featured_sort_order')->default(0)->after('featured_until');
            $table->index(['featured_on_home', 'is_active', 'featured_sort_order'], 'posts_home_featured_idx');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_home_featured_idx');
            $table->dropColumn([
                'featured_on_home',
                'featured_from',
                'featured_until',
                'featured_sort_order',
            ]);
        });
    }
};
