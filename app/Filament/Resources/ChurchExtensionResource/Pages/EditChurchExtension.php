<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchExtensionResource\Pages;

use App\Filament\Resources\ChurchExtensionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d'une extension CMP.
 */
class EditChurchExtension extends EditRecord
{
    protected static string $resource = ChurchExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
