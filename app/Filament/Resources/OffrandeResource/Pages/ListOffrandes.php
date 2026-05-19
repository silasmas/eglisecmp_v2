<?php

namespace App\Filament\Resources\OffrandeResource\Pages;

use App\Filament\Resources\OffrandeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOffrandes extends ListRecords
{
    protected static string $resource = OffrandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
