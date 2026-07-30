<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorshipServiceReportResource\Pages;

use App\Filament\Resources\WorshipServiceReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Consultation d'un rapport de culte.
 */
class ViewWorshipServiceReport extends ViewRecord
{
    protected static string $resource = WorshipServiceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
