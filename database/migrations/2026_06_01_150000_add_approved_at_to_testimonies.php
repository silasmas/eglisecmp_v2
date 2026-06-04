<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Date de validation (publication sur le mur) pour l’affichage « il y a X ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('testimonies', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('status');
        });

        DB::table('testimonies')
            ->where('status', 'approved')
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('testimonies', function (Blueprint $table): void {
            $table->dropColumn('approved_at');
        });
    }
};
