<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supprime les rôles Spatie en double (même nom + guard) en conservant le plus ancien ID.
 * Tolère les schémas legacy incomplets sans faire échouer la sync.
 */
return new class extends Migration
{
    /**
     * Déduplique les rôles Spatie si le schéma le permet.
     *
     * @return void
     */
    public function up(): void
    {
        $table = (string) config('permission.table_names.roles', 'roles');
        $pivotRoles = (string) config('permission.table_names.model_has_roles', 'model_has_roles');
        $pivotPerms = (string) config('permission.table_names.role_has_permissions', 'role_has_permissions');

        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, 'name') || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $hasGuard = Schema::hasColumn($table, 'guard_name');

        try {
            $query = DB::table($table)->select('name', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'));

            if ($hasGuard) {
                $query->addSelect('guard_name')->groupBy('name', 'guard_name');
            } else {
                $query->groupBy('name');
            }

            $duplicates = $query->having('cnt', '>', 1)->get();
        } catch (Throwable $e) {
            // Schéma incompatible (legacy) : on ignore sans bloquer.
            return;
        }

        foreach ($duplicates as $row) {
            $keepId = (int) $row->keep_id;
            $dupQuery = DB::table($table)
                ->where('name', $row->name)
                ->where('id', '!=', $keepId);

            if ($hasGuard && isset($row->guard_name)) {
                $dupQuery->where('guard_name', $row->guard_name);
            }

            $duplicateIds = $dupQuery->pluck('id');

            foreach ($duplicateIds as $duplicateId) {
                try {
                    if (Schema::hasTable($pivotPerms) && Schema::hasColumn($pivotPerms, 'role_id')) {
                        DB::table($pivotPerms)->where('role_id', $duplicateId)->delete();
                    }

                    if (
                        Schema::hasTable($pivotRoles)
                        && Schema::hasColumn($pivotRoles, 'role_id')
                        && Schema::hasColumn($pivotRoles, 'model_type')
                        && Schema::hasColumn($pivotRoles, 'model_id')
                    ) {
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
                } catch (Throwable $e) {
                    // Doublon déjà nettoyé / contrainte FK : on continue.
                    continue;
                }
            }
        }
    }

    /**
     * Irréversible : les doublons supprimés ne sont pas recréés.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
};
