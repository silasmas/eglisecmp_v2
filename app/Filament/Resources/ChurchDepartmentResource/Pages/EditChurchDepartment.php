<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchDepartmentResource\Pages;

use App\Filament\Resources\ChurchDepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/** Édition département. */
class EditChurchDepartment extends EditRecord
{
    protected static string $resource = ChurchDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
