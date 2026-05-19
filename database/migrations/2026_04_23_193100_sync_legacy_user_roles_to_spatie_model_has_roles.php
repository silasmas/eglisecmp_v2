<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_roles') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        $legacyRows = DB::table('user_roles as ur')
            ->join('roles as r', 'r.id', '=', 'ur.role_id')
            ->join('users as u', 'u.id', '=', 'ur.user_id')
            ->select(['ur.role_id', 'ur.user_id'])
            ->get();

        if ($legacyRows->isEmpty()) {
            return;
        }

        $payload = $legacyRows
            ->map(fn ($row): array => [
                'role_id' => (int) $row->role_id,
                'model_type' => 'App\\Models\\User',
                'model_id' => (int) $row->user_id,
            ])
            ->all();

        DB::table('model_has_roles')->upsert(
            $payload,
            ['role_id', 'model_id', 'model_type'],
            []
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('model_has_roles')) {
            return;
        }

        DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->delete();
    }
};
