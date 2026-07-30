<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorshipServiceReportResource\Pages;

use App\Filament\Resources\WorshipServiceReportResource;
use App\Filament\Widgets\WorshipAttendanceStatsOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des rapports de culte avec statistiques.
 */
class ListWorshipServiceReports extends ListRecords
{
    protected static string $resource = WorshipServiceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            WorshipAttendanceStatsOverviewWidget::class,
        ];
    }
}
