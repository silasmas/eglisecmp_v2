<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDeployToken;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

/**
 * Régénère les permissions Shield et resynchronise le rôle super_admin via HTTP.
 */
final class ShieldSyncController extends Controller
{
  use ValidatesDeployToken;

  /**
   * Génère les permissions Filament Shield et met à jour le rôle super_admin.
   *
   * @param  string  $token  Jeton secret défini dans DEPLOY_TOKEN (.env).
   */
  public function __invoke(string $token): JsonResponse
  {
    $authError = $this->validateDeployToken($token);

    if ($authError !== null) {
      return $authError;
    }

    $generateExitCode = Artisan::call('shield:generate', [
      '--all' => true,
      '--panel' => 'admin',
      '--option' => 'permissions',
    ]);

    $generateOutput = trim(Artisan::output());

    if ($generateExitCode !== 0) {
      return response()->json([
        'success' => false,
        'message' => 'Échec de shield:generate.',
        'generate_output' => $generateOutput,
        'exit_code' => $generateExitCode,
      ], 500);
    }

    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $superAdminRole = Utils::createRole();
    $permissionIds = Utils::getPermissionModel()::pluck('id');
    $superAdminRole->syncPermissions($permissionIds);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $assignedUsers = $this->assignSuperAdminRole($superAdminRole->name);

    return response()->json([
      'success' => true,
      'message' => 'Permissions Shield régénérées et rôle super_admin synchronisé.',
      'permissions_count' => $permissionIds->count(),
      'super_admin_role' => $superAdminRole->name,
      'assigned_users' => $assignedUsers,
      'generate_output' => $generateOutput,
    ]);
  }

  /**
   * Attribue le rôle super_admin à l’utilisateur cible.
   *
   * @param  string  $roleName  Nom du rôle super_admin Shield.
   * @return list<array{id: int|string, email: string|null}> Utilisateurs mis à jour.
   */
  private function assignSuperAdminRole(string $roleName): array
  {
    $userIds = $this->resolveSuperAdminUserIds();

    if ($userIds === []) {
      return [];
    }

    $assignedUsers = [];

    foreach ($userIds as $userId) {
      $user = User::query()->find($userId);

      if ($user === null) {
        continue;
      }

      $user->unsetRelation('roles')->unsetRelation('permissions');
      $user->syncRoles([$roleName]);

      $assignedUsers[] = [
        'id' => $user->getKey(),
        'email' => $user->email,
      ];
    }

    return $assignedUsers;
  }

  /**
   * Détermine les utilisateurs à promouvoir super_admin.
   *
   * @return list<int|string> Identifiants utilisateur.
   */
  private function resolveSuperAdminUserIds(): array
  {
    $configuredUserId = (string) config('app.shield_super_admin_user_id');

    if ($configuredUserId !== '') {
      return [$configuredUserId];
    }

    return User::role(Utils::getSuperAdminName())
      ->pluck('id')
      ->all();
  }
}
