<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * Graphique mensuel des transactions d’offrandes enregistrées.
 */
class SiteTransactionsTrendChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Transactions d’offrandes (6 derniers mois)';

    protected ?string $description = 'Toutes les transactions enregistrées, tous statuts confondus.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $labels = [];
        $totalCounts = [];
        $successCounts = [];

        $start = Carbon::now()->startOfMonth()->subMonths(5);

        for ($index = 0; $index < 6; $index++) {
            $monthStart = $start->copy()->addMonths($index);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $labels[] = $monthStart->translatedFormat('M Y');

            $totalCounts[] = Transaction::query()
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();

            $successCounts[] = Transaction::query()
                ->where('etat', 'paid')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total',
                    'data' => $totalCounts,
                    'backgroundColor' => 'rgba(180, 83, 9, 0.55)',
                ],
                [
                    'label' => 'Réussies',
                    'data' => $successCounts,
                    'backgroundColor' => 'rgba(22, 163, 74, 0.65)',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
