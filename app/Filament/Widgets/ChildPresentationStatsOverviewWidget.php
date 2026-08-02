<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\PresentedChild;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Statistiques des enfants présentés (mois, trimestre, semestre, année).
 */
class ChildPresentationStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    /**
     * Visible si l’utilisateur peut gérer les présentations d’enfants.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->can('ViewAny:ChildPresentation')
            || $user->can('ViewAny:PresentedChild')
            || (method_exists($user, 'hasRole') && $user->hasRole('super_admin'))
        );
    }

    /**
     * Compte les enfants liés à des présentations confirmées sur une période.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $now = Carbon::now();

        return [
            Stat::make('Ce mois', (string) $this->countConfirmedChildren(
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ))
                ->description('Enfants présentés')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('success'),
            Stat::make('Ce trimestre', (string) $this->countConfirmedChildren(
                $now->copy()->firstOfQuarter(),
                $now->copy()->lastOfQuarter(),
            ))
                ->description('Enfants présentés')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),
            Stat::make('Ce semestre', (string) $this->countConfirmedChildren(
                $this->semesterStart($now),
                $this->semesterEnd($now),
            ))
                ->description('Enfants présentés')
                ->descriptionIcon('heroicon-o-presentation-chart-line')
                ->color('warning'),
            Stat::make('Cette année', (string) $this->countConfirmedChildren(
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
            ))
                ->description('Enfants présentés')
                ->descriptionIcon('heroicon-o-trophy')
                ->color('primary'),
        ];
    }

    /**
     * Nombre d'enfants sur des présentations confirmées dont la date tombe dans la période.
     */
    private function countConfirmedChildren(Carbon $from, Carbon $to): int
    {
        return PresentedChild::query()
            ->whereHas('presentation', function ($query) use ($from, $to): void {
                $query->where('status', 'confirmed')
                    ->whereBetween('presentation_date', [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]);
            })
            ->count();
    }

    /**
     * Début du semestre civil (jan–juin ou juil–déc).
     */
    private function semesterStart(Carbon $now): Carbon
    {
        if ($now->month <= 6) {
            return $now->copy()->startOfYear();
        }

        return $now->copy()->month(7)->startOfMonth();
    }

    /**
     * Fin du semestre civil.
     */
    private function semesterEnd(Carbon $now): Carbon
    {
        if ($now->month <= 6) {
            return $now->copy()->month(6)->endOfMonth();
        }

        return $now->copy()->endOfYear();
    }
}
