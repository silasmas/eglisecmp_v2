<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonnements opt-in aux alertes live YouTube et événements du site.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 190)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('name', 120)->nullable();
            $table->boolean('notify_live')->default(false);
            $table->boolean('notify_events')->default(false);
            $table->string('source', 40)->default('site');
            $table->uuid('unsubscribe_token')->unique();
            $table->timestamps();

            $table->index(['notify_live', 'email']);
            $table->index(['notify_events', 'email']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_subscriptions');
    }
};
