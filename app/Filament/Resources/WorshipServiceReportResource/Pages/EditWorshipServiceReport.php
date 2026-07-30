<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorshipServiceReportResource\Pages;

use App\Filament\Resources\WorshipServiceReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d'un rapport de culte.
 */
class EditWorshipServiceReport extends EditRecord
{
    protected static string $resource = WorshipServiceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
