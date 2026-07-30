<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProtocolReporterResource\Pages;

use App\Filament\Resources\ProtocolReporterResource;
use Filament\Resources\Pages\CreateRecord;

/** Création d'un rapporteur protocole. */
class CreateProtocolReporter extends CreateRecord
{
    protected static string $resource = ProtocolReporterResource::class;
}
