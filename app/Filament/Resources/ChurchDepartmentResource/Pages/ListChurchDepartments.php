<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchDepartmentResource\Pages;

use App\Filament\Resources\ChurchDepartmentResource;
use App\Filament\Resources\Concerns\HasExcelImportActions;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/** Liste des départements. */
class ListChurchDepartments extends ListRecords
{
    use HasExcelImportActions;

    protected static string $resource = ChurchDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->excelImportHeaderActions('departements'),
            CreateAction::make(),
        ];
    }
}
