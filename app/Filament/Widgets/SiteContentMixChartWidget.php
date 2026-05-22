<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Gallery;
use App\Models\Post;
use App\Models\SiteInquiry;
use Filament\Widgets\ChartWidget;

/**
 * Répartition des principaux contenus éditoriaux et formulaires publics.
 */
class SiteContentMixChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Répartition du contenu';

    protected ?string $description = 'Publications, événements, galeries et requêtes de prière.';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $posts = Post::query()->count();
        $events = Event::query()->count();
        $galleries = Gallery::query()->count();
        $prayers = SiteInquiry::query()
            ->where('kind', SiteInquiry::KIND_PRAYER)
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Contenus',
                    'data' => [$posts, $events, $galleries, $prayers],
                    'backgroundColor' => [
                        'rgba(180, 83, 9, 0.85)',
                        'rgba(37, 99, 235, 0.75)',
                        'rgba(124, 58, 237, 0.75)',
                        'rgba(127, 29, 29, 0.85)',
                    ],
                ],
            ],
            'labels' => [
                'Publications',
                'Événements',
                'Galeries',
                'Requêtes de prière',
            ],
        ];
    }
}
