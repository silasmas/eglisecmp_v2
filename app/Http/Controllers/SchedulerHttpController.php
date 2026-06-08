<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesDeployToken;
use App\Services\SiteSchedulerRunner;
use Illuminate\Http\JsonResponse;

/**
 * Déclenche le scheduler Laravel via HTTP (alternative au cron système).
 */
final class SchedulerHttpController extends Controller
{
    use ValidatesDeployToken;

    /**
     * Exécute schedule:run si le jeton et l’interrupteur admin sont valides.
     *
     * @param  string  $token  Jeton DEPLOY_TOKEN.
     */
    public function __invoke(string $token): JsonResponse
    {
        $authError = $this->validateDeployToken($token);

        if ($authError !== null) {
            return $authError;
        }

        if (! SiteSchedulerRunner::isHttpCronEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Le cron HTTP est désactivé. Activez-le dans Admin → Tâches planifiées.',
            ], 403);
        }

        $result = SiteSchedulerRunner::run('http');

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Scheduler exécuté avec succès.'
                : ($result['error'] ?? 'Échec du scheduler.'),
            'data' => $result,
        ], $result['success'] ? 200 : 500);
    }
}
