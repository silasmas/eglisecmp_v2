<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchWorkerResource\Pages;

use App\Filament\Resources\ChurchWorkerResource;
use App\Filament\Resources\Concerns\HasWorkerStudioActions;
use Filament\Resources\Pages\ListRecords;

/** Liste des ouvriers. */
class ListChurchWorkers extends ListRecords
{
    use HasWorkerStudioActions;

    protected static string $resource = ChurchWorkerResource::class;

    protected function getHeaderActions(): array
    {
        return $this->workerStudioHeaderActions();
    }
}
