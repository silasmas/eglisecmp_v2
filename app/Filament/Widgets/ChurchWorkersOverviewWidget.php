<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\ChurchWorkerResource;
use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Statistiques ouvriers / départements visibles selon le rôle connecté.
 */
class ChurchWorkersOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Ouvriers & départements';

    protected ?string $description = 'Vue filtrée selon vos droits (admin ou responsable).';

    protected int|string|array $columnSpan = 'full';

    /**
     * Visible uniquement pour les utilisateurs ayant accès aux ouvriers.
     */
    public static function canView(): bool
    {
        return ChurchWorkerResource::canAccess();
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $workers = $this->workersQuery();
        $pending = (clone $workers)->where('status', ChurchWorker::STATUS_PENDING)->count();
        $approved = (clone $workers)->where('status', ChurchWorker::STATUS_APPROVED)->count();
        $badges = (clone $workers)->where('badge_generated', true)->count();
        $departments = $this->departmentsCount();

        return [
            Stat::make('Départements', (string) $departments)
                ->description('Actifs visibles')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('gray'),
            Stat::make('En attente', (string) $pending)
                ->description('À valider')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Validés', (string) $approved)
                ->description('Dossiers approuvés')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Badges générés', (string) $badges)
                ->description('Prêts à exporter')
                ->descriptionIcon('heroicon-m-identification')
                ->color('primary'),
        ];
    }

    /**
     * Requête ouvriers scopée (mêmes règles que la ressource).
     *
     * @return Builder<ChurchWorker>
     */
    private function workersQuery(): Builder
    {
        return ChurchWorkerResource::getEloquentQuery();
    }

    /**
     * Nombre de départements visibles pour l’utilisateur.
     */
    private function departmentsCount(): int
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return 0;
        }

        if ($user->hasRole('super_admin') || $user->can('ViewAny:ChurchDepartment')) {
            return ChurchDepartment::query()->where('is_active', true)->count();
        }

        return ChurchDepartment::query()
            ->where('manager_user_id', $user->id)
            ->where('is_active', true)
            ->count();
    }
}
