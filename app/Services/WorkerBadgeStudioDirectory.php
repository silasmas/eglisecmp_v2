<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use App\Models\User;
use App\Support\FilamentImageUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Données départements / ouvriers pour le studio badges.
 */
final class WorkerBadgeStudioDirectory
{
    /**
     * Charge le payload JSON (départements + ouvriers validés) pour un utilisateur.
     *
     * @param  User  $user  Utilisateur connecté.
     * @param  int|null  $departmentId  Filtre optionnel.
     * @return array{departments: list<array<string, mixed>>, workers: list<array<string, mixed>>}
     */
    public function payloadFor(User $user, ?int $departmentId = null): array
    {
        $departments = $this->departmentsFor($user);

        $workersQuery = ChurchWorker::query()
            ->with('department:id,name,slug,color')
            ->where(function ($query): void {
                $query->where('status', ChurchWorker::STATUS_APPROVED)
                    ->orWhere('badge_generated', true);
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($departmentId !== null) {
            $workersQuery->where('department_id', $departmentId);
        }

        // Les responsables non globaux ne voient que leurs départements.
        if (! $this->canSeeAllDepartments($user)) {
            $managedIds = ChurchDepartment::query()
                ->where('manager_user_id', $user->id)
                ->pluck('id');
            $workersQuery->whereIn('department_id', $managedIds->isEmpty() ? [-1] : $managedIds->all());
        }

        $workers = $workersQuery->get()->map(fn (ChurchWorker $worker): array => $this->serializeWorker($worker))->values()->all();

        return [
            'departments' => $departments
                ->map(fn (ChurchDepartment $d): array => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'slug' => $d->slug,
                    'color' => $d->color ?: '#7b1d3e',
                ])
                ->values()
                ->all(),
            'workers' => $workers,
        ];
    }

    /**
     * Départements visibles dans le studio (tous les actifs pour tout accès studio).
     *
     * @return Collection<int, ChurchDepartment>
     */
    public function departmentsFor(User $user): Collection
    {
        // Le studio liste toujours tous les départements actifs (filtre UI).
        $active = ChurchDepartment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color', 'is_active']);

        if ($active->isNotEmpty()) {
            return $active;
        }

        return ChurchDepartment::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color', 'is_active']);
    }

    /**
     * Admin global ou droit de voir tous les ouvriers / départements.
     */
    public function canSeeAllDepartments(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('ViewAny:ChurchWorker')
            || $user->can('ViewAny:ChurchDepartment')
            || $user->can('Update:ChurchWorker');
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeWorker(ChurchWorker $worker): array
    {
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
    }
}
