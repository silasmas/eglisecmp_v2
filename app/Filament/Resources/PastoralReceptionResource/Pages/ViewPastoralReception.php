<?php

declare(strict_types=1);

namespace App\Filament\Resources\PastoralReceptionResource\Pages;

use App\Filament\Resources\PastoralReceptionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/** Consultation d'un dossier RDV pastoral. */
class ViewPastoralReception extends ViewRecord
{
    protected static string $resource = PastoralReceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Compléter le dossier'),
        ];
    }
}
