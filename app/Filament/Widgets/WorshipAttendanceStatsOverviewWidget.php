<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\WorshipServiceReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Statistiques de fréquentation des cultes (semaine, mois, année).
 */
class WorshipAttendanceStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $now = Carbon::now();

        return [
            Stat::make('Cette semaine', (string) $this->sumBetween(
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ))
                ->description('Participants')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('success'),
            Stat::make('Ce mois', (string) $this->sumBetween(
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ))
                ->description('Participants')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),
            Stat::make('Cette année', (string) $this->sumBetween(
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear(),
            ))
                ->description('Participants')
                ->descriptionIcon('heroicon-o-trophy')
                ->color('primary'),
            Stat::make('Rapports', (string) WorshipServiceReport::query()->count())
                ->description('Total saisis')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('warning'),
        ];
    }

    /**
     * Somme des participants sur une période.
     */
    private function sumBetween(Carbon $from, Carbon $to): int
    {
        return (int) WorshipServiceReport::query()
            ->whereBetween('service_date', [$from->toDateString(), $to->toDateString()])
            ->sum('attendees_count');
    }
}
