<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\SiteInquiry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Graphique mensuel des demandes prière et rendez-vous reçues sur le site.
 */
class SiteInquiriesTrendChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Demandes reçues (6 derniers mois)';

    protected ?string $description = 'Requêtes de prière et demandes de rendez-vous.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $labels = [];
        $prayerCounts = [];
        $appointmentCounts = [];

        $start = Carbon::now()->startOfMonth()->subMonths(5);

        for ($index = 0; $index < 6; $index++) {
            $monthStart = $start->copy()->addMonths($index);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $labels[] = $monthStart->translatedFormat('M Y');

            $prayerCounts[] = SiteInquiry::query()
                ->where('kind', SiteInquiry::KIND_PRAYER)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $appointmentCounts[] = SiteInquiry::query()
                ->where('kind', SiteInquiry::KIND_APPOINTMENT)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Requêtes de prière',
                    'data' => $prayerCounts,
                    'borderColor' => '#7f1d1d',
                    'backgroundColor' => 'rgba(127, 29, 29, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Rendez-vous',
                    'data' => $appointmentCounts,
                    'borderColor' => '#b45309',
                    'backgroundColor' => 'rgba(180, 83, 9, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
