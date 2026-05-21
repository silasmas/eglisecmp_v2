<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * Vérifie le jeton DEPLOY_TOKEN pour les endpoints de déploiement HTTP.
 */
trait ValidatesDeployToken
{
  /**
   * Valide le jeton fourni dans l’URL.
   *
   * @param  string  $token  Jeton secret transmis dans la requête.
   * @return JsonResponse|null Réponse d’erreur, ou null si le jeton est valide.
   */
  protected function validateDeployToken(string $token): ?JsonResponse
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

    return null;
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
