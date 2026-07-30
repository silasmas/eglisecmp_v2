<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchWorkerResource\Pages;

use App\Filament\Resources\ChurchWorkerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Détail d'un ouvrier. */
class ViewChurchWorker extends ViewRecord
{
    protected static string $resource = ChurchWorkerResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
