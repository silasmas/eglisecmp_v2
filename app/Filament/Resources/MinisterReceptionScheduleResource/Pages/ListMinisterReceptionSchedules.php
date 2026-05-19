<?php

declare(strict_types=1);

namespace App\Filament\Resources\MinisterReceptionScheduleResource\Pages;

use App\Filament\Resources\MinisterReceptionScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMinisterReceptionSchedules extends ListRecords
{
    protected static string $resource = MinisterReceptionScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
