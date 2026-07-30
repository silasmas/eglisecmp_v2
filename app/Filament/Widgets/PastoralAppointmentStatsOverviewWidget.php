<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SiteInquiry;
use App\Models\User;
use App\Support\AppointmentReasons;
use App\Support\PastoralAccess;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Stats des rendez-vous pastoraux (jour / semaine / mois) + répartition motifs.
 */
class PastoralAppointmentStatsOverviewWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Historique rendez-vous';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $base = $this->baseQuery();

        $today = (clone $base)->whereDate('preferred_at', today())->count();
        $week = (clone $base)->whereBetween('preferred_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $month = (clone $base)->whereBetween('preferred_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $year = (clone $base)->whereBetween('preferred_at', [now()->startOfYear(), now()->endOfYear()])->count();

        $topReason = (clone $base)
            ->whereNotNull('appointment_reason')
            ->selectRaw('appointment_reason, COUNT(*) as total')
            ->groupBy('appointment_reason')
            ->orderByDesc('total')
            ->first();

        $topLabel = $topReason !== null
            ? AppointmentReasons::label((string) $topReason->appointment_reason).' ('.$topReason->total.')'
            : '—';

        return [
            Stat::make('Aujourd’hui', (string) $today)->description('RDV du jour'),
            Stat::make('Cette semaine', (string) $week)->description('Lun → Dim'),
            Stat::make('Ce mois', (string) $month)->description(now()->translatedFormat('F Y')),
            Stat::make('Cette année', (string) $year)->description('Cumul '.now()->year),
            Stat::make('Motif le plus fréquent', $topLabel)->description('Classification'),
        ];
    }

    /**
     * Requête de base scopée selon le pasteur connecté.
     *
     * @return Builder<SiteInquiry>
     */
    private function baseQuery(): Builder
    {
        $query = SiteInquiry::query()->where('kind', SiteInquiry::KIND_APPOINTMENT);

        $user = auth()->user();
        if ($user instanceof User) {
            $scopedId = PastoralAccess::scopedMinisterId($user);
            if ($scopedId !== null) {
                $query->where('minister_id', $scopedId);
            }
        }

        return $query;
    }
}
