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
            ->where('status', ChurchWorker::STATUS_APPROVED)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($departmentId !== null) {
            $workersQuery->where('department_id', $departmentId);
        }

        if (! $this->canSeeAllDepartments($user)) {
            $managedIds = $departments->pluck('id');
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
     * Départements visibles dans le studio.
     *
     * @return Collection<int, ChurchDepartment>
     */
    public function departmentsFor(User $user): Collection
    {
        $query = ChurchDepartment::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        // Actifs en priorité ; si aucun actif, on retombe sur tous (évite liste vide).
        $activeQuery = (clone $query)->where('is_active', true);
        if ($this->canSeeAllDepartments($user)) {
            $active = $activeQuery->get(['id', 'name', 'slug', 'color', 'is_active']);
            if ($active->isNotEmpty()) {
                return $active;
            }

            return $query->get(['id', 'name', 'slug', 'color', 'is_active']);
        }

        // Responsable : uniquement ses départements (actifs si possible).
        $managed = $query->where('manager_user_id', $user->id);
        $activeManaged = (clone $managed)->where('is_active', true)->get(['id', 'name', 'slug', 'color', 'is_active']);
        if ($activeManaged->isNotEmpty()) {
            return $activeManaged;
        }

        return $managed->get(['id', 'name', 'slug', 'color', 'is_active']);
    }

    /**
     * Admin global ou droit de voir tous les départements / ouvriers.
     */
    public function canSeeAllDepartments(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('ViewAny:ChurchWorker')
            || $user->can('ViewAny:ChurchDepartment');
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
