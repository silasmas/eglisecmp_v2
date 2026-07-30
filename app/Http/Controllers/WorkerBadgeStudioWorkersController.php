<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WorkerBadgeStudioDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API JSON du studio badges : ouvriers validés + départements.
 */
final class WorkerBadgeStudioWorkersController extends Controller
{
    public function __construct(
        private readonly WorkerBadgeStudioDirectory $directory,
    ) {}

    /**
     * Liste les départements et ouvriers validés pour le studio.
     *
     * @param  Request  $request  Requête HTTP (query `department_id` optionnel).
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->canAccessStudio($user)) {
            abort(403);
        }

        $departmentId = $request->integer('department_id') ?: null;

        return response()
            ->json($this->directory->payloadFor($user, $departmentId))
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Droits d’accès au studio.
     */
    private function canAccessStudio(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('ViewAny:ChurchWorker')
            || $user->can('ViewAny:ChurchDepartment')
            || $user->can('Update:ChurchWorker');
    }
}
