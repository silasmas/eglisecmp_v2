<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Permission custom : voir toutes les réponses d’accueil invités (tous départements).
 */
return new class extends Migration
{
    /**
     * Exécute la migration.
     */
    public function up(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        Permission::findOrCreate('view_all_guest_form_answers', $guard);
        Permission::findOrCreate('ViewAny:GuestPastoralProject', $guard);
        Permission::findOrCreate('View:GuestPastoralProject', $guard);
        Permission::findOrCreate('Create:GuestPastoralProject', $guard);
        Permission::findOrCreate('Update:GuestPastoralProject', $guard);
        Permission::findOrCreate('Delete:GuestPastoralProject', $guard);
        Permission::findOrCreate('DeleteAny:GuestPastoralProject', $guard);
        Permission::findOrCreate('ViewAny:GuestInfoForm', $guard);
        Permission::findOrCreate('View:GuestInfoForm', $guard);
        Permission::findOrCreate('Create:GuestInfoForm', $guard);
        Permission::findOrCreate('Update:GuestInfoForm', $guard);
        Permission::findOrCreate('Delete:GuestInfoForm', $guard);
        Permission::findOrCreate('DeleteAny:GuestInfoForm', $guard);
        Permission::findOrCreate('ViewAny:GuestInfoSubmission', $guard);
        Permission::findOrCreate('View:GuestInfoSubmission', $guard);
        Permission::findOrCreate('Delete:GuestInfoSubmission', $guard);
        Permission::findOrCreate('DeleteAny:GuestInfoSubmission', $guard);

        $super = DB::table('roles')->where('name', 'super_admin')->first();
        if ($super !== null) {
            $permissionIds = Permission::query()
                ->whereIn('name', [
                    'view_all_guest_form_answers',
                    'ViewAny:GuestPastoralProject',
                    'View:GuestPastoralProject',
                    'Create:GuestPastoralProject',
                    'Update:GuestPastoralProject',
                    'Delete:GuestPastoralProject',
                    'DeleteAny:GuestPastoralProject',
                    'ViewAny:GuestInfoForm',
                    'View:GuestInfoForm',
                    'Create:GuestInfoForm',
                    'Update:GuestInfoForm',
                    'Delete:GuestInfoForm',
                    'DeleteAny:GuestInfoForm',
                    'ViewAny:GuestInfoSubmission',
                    'View:GuestInfoSubmission',
                    'Delete:GuestInfoSubmission',
                    'DeleteAny:GuestInfoSubmission',
                ])
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->updateOrInsert(
                    ['permission_id' => $permissionId, 'role_id' => $super->id],
                    [],
                );
            }
        }
    }

    /**
     * Annule la migration.
     */
    public function down(): void
    {
        Permission::query()
            ->whereIn('name', [
                'view_all_guest_form_answers',
                'ViewAny:GuestPastoralProject',
                'View:GuestPastoralProject',
                'Create:GuestPastoralProject',
                'Update:GuestPastoralProject',
                'Delete:GuestPastoralProject',
                'DeleteAny:GuestPastoralProject',
                'ViewAny:GuestInfoForm',
                'View:GuestInfoForm',
                'Create:GuestInfoForm',
                'Update:GuestInfoForm',
                'Delete:GuestInfoForm',
                'DeleteAny:GuestInfoForm',
                'ViewAny:GuestInfoSubmission',
                'View:GuestInfoSubmission',
                'Delete:GuestInfoSubmission',
                'DeleteAny:GuestInfoSubmission',
            ])
            ->delete();
    }
};
