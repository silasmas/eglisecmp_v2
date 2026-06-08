<?php

declare(strict_types=1);

namespace App\Filament\Resources\BundaProgramResource\Pages;

use App\Filament\Resources\BundaProgramResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d’un programme Bunda.
 */
class EditBundaProgram extends EditRecord
{
    protected static string $resource = BundaProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
