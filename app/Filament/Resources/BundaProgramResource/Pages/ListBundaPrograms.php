<?php

declare(strict_types=1);

namespace App\Filament\Resources\BundaProgramResource\Pages;

use App\Filament\Resources\BundaProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des programmes Bunda 21.
 */
class ListBundaPrograms extends ListRecords
{
    protected static string $resource = BundaProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
