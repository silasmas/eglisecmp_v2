<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDeployToken;
use App\Services\DatabaseSyncRunner;
use Illuminate\Http\JsonResponse;

/**
 * Exécute `php artisan migrate --force` via HTTP (déploiement sans accès SSH).
 */
final class MigrateController extends Controller
{
    use ValidatesDeployToken;

    /**
     * Applique les migrations en attente si le jeton est valide.
     *
     * @param  string  $token  Jeton secret défini dans DEPLOY_TOKEN (.env).
     */
    public function __invoke(string $token): JsonResponse
    {
        $authError = $this->validateDeployToken($token);

        if ($authError !== null) {
            return $authError;
        }

        $before = DatabaseSyncRunner::status();
        $result = DatabaseSyncRunner::migrate('http-deploy');

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Migrations exécutées.'
                : ($result['error'] ?? 'Échec des migrations.'),
            'pending_before' => $before['pending_count'],
            'pending_after' => DatabaseSyncRunner::status()['pending_count'],
            'output' => $result['output'],
            'ran_at' => $result['ran_at'],
        ], $result['success'] ? 200 : 500);
    }
}
