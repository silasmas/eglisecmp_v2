<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchCellResource\Pages;

use App\Filament\Resources\ChurchCellResource;
use App\Filament\Resources\Concerns\HasExcelImportActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des cellules de maison.
 */
class ListChurchCells extends ListRecords
{
    use HasExcelImportActions;

    protected static string $resource = ChurchCellResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->excelImportHeaderActions('cellules'),
            CreateAction::make(),
        ];
    }
}
