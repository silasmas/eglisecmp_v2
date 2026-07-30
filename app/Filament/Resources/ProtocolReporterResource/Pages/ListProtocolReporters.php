<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProtocolReporterResource\Pages;

use App\Filament\Resources\ProtocolReporterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/** Liste des rapporteurs protocole. */
class ListProtocolReporters extends ListRecords
{
    protected static string $resource = ProtocolReporterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
