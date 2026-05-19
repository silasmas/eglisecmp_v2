<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles legacy.
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250)->nullable();
            $table->string('display_name', 250)->nullable();
            $table->string('guard_name');
            $table->timestamps();
        });

        // Permissions legacy.
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        // Pivot role <-> permission.
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
            $table->index('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        // Pivot user <-> role.
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['user_id', 'role_id']);
            $table->index('user_id');
            $table->index('role_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('role_id')->references('id')->on('roles');
        });

        // Codes OTP/verification.
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
            $table->index(['email', 'code']);
        });

        // Ancienne table password_resets presente dans le dump.
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email', 191);
            $table->string('token', 191);
            $table->timestamp('created_at')->nullable();
            $table->index('email');
        });

        // Extension de la table users Laravel pour coller au schema legacy.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->string('avatar', 191)->default('user/default.png')->after('email');
            $table->string('role')->nullable()->after('remember_token');
            $table->text('settings')->nullable()->after('role');
            $table->integer('notifiable')->default(0)->after('settings');
            $table->integer('organiser_id')->nullable()->after('notifiable');

            $table->index('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
            $table->dropColumn(['role_id', 'avatar', 'role', 'settings', 'notifiable', 'organiser_id']);
        });

        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('verification_codes');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
