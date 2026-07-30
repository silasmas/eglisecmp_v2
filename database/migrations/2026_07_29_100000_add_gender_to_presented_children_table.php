<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le sexe de l’enfant aux fiches de présentation.
 */
return new class extends Migration
{
    /**
     * Ajoute la colonne gender sur presented_children.
     */
    public function up(): void
    {
        Schema::table('presented_children', function (Blueprint $table): void {
            $table->string('gender', 16)->default('male')->after('full_name');
        });
    }

    /**
     * Supprime la colonne gender.
     */
    public function down(): void
    {
        Schema::table('presented_children', function (Blueprint $table): void {
            $table->dropColumn('gender');
        });
    }
};
