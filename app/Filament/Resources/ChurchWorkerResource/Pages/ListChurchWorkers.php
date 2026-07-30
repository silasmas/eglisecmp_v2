<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchWorkerResource\Pages;

use App\Filament\Resources\ChurchWorkerResource;
use Filament\Resources\Pages\ListRecords;

/** Liste des ouvriers. */
class ListChurchWorkers extends ListRecords
{
    protected static string $resource = ChurchWorkerResource::class;
}
