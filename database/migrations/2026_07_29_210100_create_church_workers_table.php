<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscriptions ouvriers (dossier + badge + lien user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('church_workers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained('church_departments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('last_name');
            $table->string('first_name');
            $table->string('gender', 16);
            $table->date('birth_date');
            $table->string('phone', 32);
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('city')->default('Kinshasa');
            $table->string('commune');
            $table->string('quartier');
            $table->string('avenue');
            $table->string('address_reference')->nullable();

            $table->string('studies')->nullable();
            $table->string('education_level')->nullable();
            $table->string('profession')->nullable();
            $table->text('skills')->nullable();
            $table->string('department_role')->nullable();
            $table->date('department_joined_at')->nullable();

            $table->string('photo_path')->nullable();
            $table->string('status', 32)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->uuid('badge_token')->unique();
            $table->boolean('badge_generated')->default(false);
            $table->timestamp('badge_generated_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'department_id']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('church_workers');
    }
};
