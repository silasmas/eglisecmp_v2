<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Étend le mur de témoignages : anonymat, vidéo fichier, refus, partages, réglages admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonies', function (Blueprint $table): void {
            $table->boolean('is_anonymous')->default(false)->after('verification_type');
            $table->string('video_file')->nullable()->after('video');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->unsignedInteger('share_count')->default(0)->after('rejection_reason');
        });

        Schema::create('testimony_wall_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('allow_photo_upload')->default(true);
            $table->timestamps();
        });

        DB::table('testimony_wall_settings')->insert([
            'allow_photo_upload' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('testimony_wall_settings');

        Schema::table('testimonies', function (Blueprint $table): void {
            $table->dropColumn([
                'is_anonymous',
                'video_file',
                'rejection_reason',
                'share_count',
            ]);
        });
    }
};
