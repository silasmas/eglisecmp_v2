<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\Pages;

use App\Filament\Resources\GuestPastoralProjectResource;
use Filament\Resources\Pages\CreateRecord;

/** Création d’un projet d’accueil. */
class CreateGuestPastoralProject extends CreateRecord
{
    protected static string $resource = GuestPastoralProjectResource::class;
}
