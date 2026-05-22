<?php

declare(strict_types=1);

namespace App\Filament\Resources\BureauResource\Pages;

use App\Filament\Resources\BureauResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des bureaux de réception.
 */
class ListBureaus extends ListRecords
{
    protected static string $resource = BureauResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
