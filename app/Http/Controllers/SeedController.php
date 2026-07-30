<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDeployToken;
use App\Services\DatabaseSyncRunner;
use Illuminate\Http\JsonResponse;

/**
 * Exécute les seeders sûrs via HTTP (déploiement sans accès SSH).
 */
final class SeedController extends Controller
{
    use ValidatesDeployToken;

    /**
     * Lance les seeders idempotents si le jeton est valide.
     *
     * @param  string  $token  Jeton secret défini dans DEPLOY_TOKEN (.env).
     */
    public function __invoke(string $token): JsonResponse
    {
        $authError = $this->validateDeployToken($token);

        if ($authError !== null) {
            return $authError;
        }

        $result = DatabaseSyncRunner::seed('http-deploy');

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Seeders exécutés.'
                : ($result['error'] ?? 'Échec des seeders.'),
            'seeders' => DatabaseSyncRunner::safeSeederLabels(),
            'output' => $result['output'],
            'ran_at' => $result['ran_at'],
        ], $result['success'] ? 200 : 500);
    }
}
