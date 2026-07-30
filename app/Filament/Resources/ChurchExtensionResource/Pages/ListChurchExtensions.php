<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchExtensionResource\Pages;

use App\Filament\Resources\ChurchExtensionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des extensions CMP.
 */
class ListChurchExtensions extends ListRecords
{
    protected static string $resource = ChurchExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
