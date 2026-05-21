<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDeployToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * Exécute `php artisan storage:link` via HTTP (déploiement sans accès SSH).
 */
final class StorageLinkController extends Controller
{
  use ValidatesDeployToken;

  /**
   * Crée le lien symbolique public/storage si le jeton est valide.
   *
   * @param  string  $token  Jeton secret défini dans DEPLOY_TOKEN (.env).
   */
  public function __invoke(string $token): JsonResponse
  {
    $authError = $this->validateDeployToken($token);

    if ($authError !== null) {
      return $authError;
    }

    $exitCode = Artisan::call('storage:link');
    $output = trim(Artisan::output());

    return response()->json([
      'success' => $exitCode === 0,
      'message' => $output !== '' ? $output : ($exitCode === 0 ? 'Lien storage créé.' : 'Échec de storage:link.'),
      'exit_code' => $exitCode,
    ], $exitCode === 0 ? 200 : 500);
  }
}
