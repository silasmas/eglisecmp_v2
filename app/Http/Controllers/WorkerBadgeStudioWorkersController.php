<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use App\Models\User;
use App\Support\FilamentImageUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * API JSON du studio badges : ouvriers validés + départements.
 */
final class WorkerBadgeStudioWorkersController extends Controller
{
    /**
     * Liste les ouvriers validés (et optionnellement filtrés) pour le studio.
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

        $departmentsQuery = ChurchDepartment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $user->hasRole('super_admin') && ! $user->can('ViewAny:ChurchWorker')) {
            $departmentsQuery->where('manager_user_id', $user->id);
        }

        $departments = $departmentsQuery->get(['id', 'name', 'slug', 'color']);

        $workersQuery = ChurchWorker::query()
            ->with('department:id,name,slug,color')
            ->where('status', ChurchWorker::STATUS_APPROVED)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($departmentId !== null) {
            $workersQuery->where('department_id', $departmentId);
        }

        if (! $user->hasRole('super_admin') && ! $user->can('ViewAny:ChurchWorker')) {
            $managedIds = $departments->pluck('id');
            $workersQuery->whereIn('department_id', $managedIds);
        }

        $workers = $workersQuery->get()->map(function (ChurchWorker $worker): array {
            $department = $worker->department;
            $slug = filled($department?->slug)
                ? (string) $department->slug
                : Str::slug((string) ($department?->name ?? 'participant'));

            return [
                'id' => 'WORKER-'.$worker->id,
                'churchWorkerId' => $worker->id,
                'source' => 'validated',
                'prenom' => $worker->first_name,
                'nom' => $worker->last_name,
                'postnom' => '',
                'sexe' => $worker->gender === ChurchWorker::GENDER_FEMALE ? 'F' : 'M',
                'category' => $slug !== '' ? $slug : 'participant',
                'atelier' => '',
                'chambre' => (string) ($worker->department_role ?? ''),
                'departmentRole' => (string) ($worker->department_role ?? ''),
                'departmentId' => $worker->department_id,
                'departmentName' => (string) ($department?->name ?? ''),
                'departmentColor' => (string) ($department?->color ?: '#7b1d3e'),
                'badgeToken' => $worker->badge_token,
                'badgeGenerated' => (bool) $worker->badge_generated,
                'photo' => FilamentImageUrl::resolve($worker->photo_path),
                'showPhoto' => true,
                'showWorkshop' => false,
                'showRoom' => filled($worker->department_role),
                'showAssignments' => filled($worker->department_role),
            ];
        })->values();

        return response()->json([
            'departments' => $departments->map(fn (ChurchDepartment $d): array => [
                'id' => $d->id,
                'name' => $d->name,
                'slug' => $d->slug,
                'color' => $d->color ?: '#7b1d3e',
            ])->values(),
            'workers' => $workers,
        ]);
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
