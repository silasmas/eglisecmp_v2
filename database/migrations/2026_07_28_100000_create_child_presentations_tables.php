<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables pour les demandes de présentation d'enfants (parents, enfants, OTP, pièces).
 */
return new class extends Migration
{
    /**
     * Crée les tables child_presentations, presented_children et phone_otps.
     */
    public function up(): void
    {
        Schema::create('child_presentations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('children_count');
            $table->string('parent_names');
            $table->string('phone', 32);
            $table->boolean('phone_verified')->default(false);
            $table->string('birth_certificate_path')->nullable();
            $table->string('parent_id_document_path')->nullable();
            $table->date('presentation_date');
            $table->string('status', 32)->default('pending');
            $table->string('confirmation_sms_status', 32)->nullable();
            $table->timestamp('confirmation_sms_sent_at')->nullable();
            $table->text('confirmation_sms_response')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'presentation_date']);
            $table->index('phone');
        });

        Schema::create('presented_children', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('child_presentation_id')
                ->constrained('child_presentations')
                ->cascadeOnDelete();
            $table->string('full_name');
            $table->unsignedTinyInteger('age_years');
            $table->unsignedTinyInteger('age_months')->default(0);
            $table->timestamps();

            $table->index('child_presentation_id');
        });

        Schema::create('phone_otps', function (Blueprint $table): void {
            $table->id();
            $table->string('phone', 32);
            $table->string('purpose', 64);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();

            $table->index(['phone', 'purpose']);
        });
    }

    /**
     * Supprime les tables de présentation d'enfants.
     */
    public function down(): void
    {
        Schema::dropIfExists('presented_children');
        Schema::dropIfExists('phone_otps');
        Schema::dropIfExists('child_presentations');
    }
};
