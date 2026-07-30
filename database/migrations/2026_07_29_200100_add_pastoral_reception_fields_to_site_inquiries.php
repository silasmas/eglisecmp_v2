<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier pastoral des rendez-vous : motif classé, notes, conclusion, orientation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->string('appointment_reason', 64)->nullable()->after('message');
            $table->string('reception_status', 32)->nullable()->after('appointment_status');
            $table->text('session_notes')->nullable()->after('reception_status');
            $table->text('session_conclusion')->nullable()->after('session_notes');
            $table->unsignedInteger('oriented_from_minister_id')->nullable()->after('minister_id');
            $table->timestamp('received_at')->nullable()->after('session_conclusion');
            $table->timestamp('completed_at')->nullable()->after('received_at');

            $table->foreign('oriented_from_minister_id')
                ->references('id')
                ->on('ministers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_inquiries', function (Blueprint $table): void {
            $table->dropForeign(['oriented_from_minister_id']);
            $table->dropColumn([
                'appointment_reason',
                'reception_status',
                'session_notes',
                'session_conclusion',
                'oriented_from_minister_id',
                'received_at',
                'completed_at',
            ]);
        });
    }
};
