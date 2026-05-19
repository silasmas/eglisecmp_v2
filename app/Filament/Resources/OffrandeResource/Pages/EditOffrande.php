<?php

namespace App\Filament\Resources\OffrandeResource\Pages;

use App\Filament\Resources\OffrandeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOffrande extends EditRecord
{
    protected static string $resource = OffrandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
