<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\Pages;

use App\Filament\Resources\GuestPastoralProjectResource;
use Filament\Resources\Pages\ListRecords;

/** Liste des projets d’accueil. */
class ListGuestPastoralProjects extends ListRecords
{
    protected static string $resource = GuestPastoralProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
