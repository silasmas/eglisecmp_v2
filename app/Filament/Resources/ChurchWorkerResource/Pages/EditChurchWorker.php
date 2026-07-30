<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchWorkerResource\Pages;

use App\Filament\Resources\ChurchWorkerResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/** Édition d'un ouvrier. */
class EditChurchWorker extends EditRecord
{
    protected static string $resource = ChurchWorkerResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }
}
