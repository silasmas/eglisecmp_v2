<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProtocolReporterResource\Pages;

use App\Filament\Resources\ProtocolReporterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/** Édition d'un rapporteur protocole. */
class EditProtocolReporter extends EditRecord
{
    protected static string $resource = ProtocolReporterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
