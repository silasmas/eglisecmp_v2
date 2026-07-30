<?php

declare(strict_types=1);

namespace App\Filament\Resources\PastoralReceptionResource\Pages;

use App\Filament\Resources\PastoralReceptionResource;
use App\Filament\Widgets\PastoralAppointmentStatsOverviewWidget;
use Filament\Resources\Pages\ListRecords;

/** Liste des dossiers de réception pastorale. */
class ListPastoralReceptions extends ListRecords
{
    protected static string $resource = PastoralReceptionResource::class;

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            PastoralAppointmentStatsOverviewWidget::class,
        ];
    }
}
