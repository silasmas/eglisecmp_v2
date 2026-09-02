<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Responsables de départements (plusieurs par département, avec contacts).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('church_department_managers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained('church_departments')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 60)->nullable();
            $table->string('email', 160)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['department_id', 'is_primary'], 'dept_mgr_primary_idx');
            $table->index(['department_id', 'sort_order'], 'dept_mgr_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('church_department_managers');
    }
};
