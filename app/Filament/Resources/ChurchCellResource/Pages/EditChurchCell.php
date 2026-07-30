<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchCellResource\Pages;

use App\Filament\Resources\ChurchCellResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d’une cellule.
 */
class EditChurchCell extends EditRecord
{
    protected static string $resource = ChurchCellResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
