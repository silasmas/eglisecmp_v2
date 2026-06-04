<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime les rôles Spatie en double (même nom + guard) en conservant le plus ancien ID.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $table = config('permission.table_names.roles', 'roles');
        $pivotRoles = config('permission.table_names.model_has_roles', 'model_has_roles');
        $pivotPerms = config('permission.table_names.role_has_permissions', 'role_has_permissions');

        $duplicates = DB::table($table)
            ->select('name', 'guard_name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('name', 'guard_name')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $row) {
            $keepId = (int) $row->keep_id;
            $duplicateIds = DB::table($table)
                ->where('name', $row->name)
                ->where('guard_name', $row->guard_name)
                ->where('id', '!=', $keepId)
                ->pluck('id');

            foreach ($duplicateIds as $duplicateId) {
                if (Schema::hasTable($pivotPerms)) {
                    DB::table($pivotPerms)->where('role_id', $duplicateId)->delete();
                }
                if (Schema::hasTable($pivotRoles)) {
                    $assignments = DB::table($pivotRoles)->where('role_id', $duplicateId)->get();
                    foreach ($assignments as $assignment) {
                        $exists = DB::table($pivotRoles)
                            ->where('role_id', $keepId)
                            ->where('model_type', $assignment->model_type)
                            ->where('model_id', $assignment->model_id)
                            ->exists();
                        if (! $exists) {
                            DB::table($pivotRoles)->insert([
                                'role_id' => $keepId,
                                'model_type' => $assignment->model_type,
                                'model_id' => $assignment->model_id,
                            ]);
                        }
                    }
                    DB::table($pivotRoles)->where('role_id', $duplicateId)->delete();
                }
                DB::table($table)->where('id', $duplicateId)->delete();
            }
        }
    }

    public function down(): void
    {
        // Irréversible : les doublons supprimés ne sont pas recréés.
    }
};
