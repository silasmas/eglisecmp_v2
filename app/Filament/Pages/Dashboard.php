<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\SiteContentMixChartWidget;
use App\Filament\Widgets\SiteInquiriesTrendChartWidget;
use App\Filament\Widgets\SiteResourcesOverviewWidget;
use App\Filament\Widgets\SiteTransactionsTrendChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Tableau de bord admin : statistiques globales et graphiques d’activité.
 */
class Dashboard extends BaseDashboard
{
    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            SiteResourcesOverviewWidget::class,
            SiteInquiriesTrendChartWidget::class,
            SiteTransactionsTrendChartWidget::class,
            SiteContentMixChartWidget::class,
        ];
    }
}
