<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;

/**
 * Exécute `php artisan storage:link` via HTTP (déploiement sans accès SSH).
 */
final class StorageLinkController extends Controller
{
  /**
   * Crée le lien symbolique public/storage si le jeton est valide.
   *
   * @param  string  $token  Jeton secret défini dans DEPLOY_TOKEN (.env).
   */
  public function __invoke(string $token): JsonResponse
  {
    $expectedToken = self::resolveDeployToken();

    if ($expectedToken === '') {
      return response()->json([
        'success' => false,
        'message' => 'DEPLOY_TOKEN non configuré dans le .env du serveur.',
      ], 503);
    }

    if (! hash_equals($expectedToken, $token)) {
      return response()->json([
        'success' => false,
        'message' => 'Non autorisé.',
      ], 403);
    }

    $exitCode = Artisan::call('storage:link');
    $output = trim(Artisan::output());

    return response()->json([
      'success' => $exitCode === 0,
      'message' => $output !== '' ? $output : ($exitCode === 0 ? 'Lien storage créé.' : 'Échec de storage:link.'),
      'exit_code' => $exitCode,
    ], $exitCode === 0 ? 200 : 500);
  }

  /**
   * Récupère DEPLOY_TOKEN (config Laravel ou lecture directe du .env si cache actif).
   *
   * @return string Jeton attendu, ou chaîne vide si absent.
   */
  private static function resolveDeployToken(): string
  {
    $fromConfig = (string) config('app.deploy_token');

    if ($fromConfig !== '') {
      return $fromConfig;
    }

    $envPath = base_path('.env');

    if (! is_readable($envPath)) {
      return '';
    }

    $contents = file_get_contents($envPath);

    if ($contents === false || ! preg_match('/^DEPLOY_TOKEN=(.*)$/m', $contents, $matches)) {
      return '';
    }

    $value = trim($matches[1]);

    if ($value === '' || $value === 'null') {
      return '';
    }

    if (
      (str_starts_with($value, '"') && str_ends_with($value, '"'))
      || (str_starts_with($value, "'") && str_ends_with($value, "'"))
    ) {
      $value = substr($value, 1, -1);
    }

    return $value;
  }
}
