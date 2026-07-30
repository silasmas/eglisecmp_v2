<?php

declare(strict_types=1);

namespace App\Filament\Resources\LoginHistoryResource\Pages;

use App\Filament\Resources\LoginHistoryResource;
use Filament\Resources\Pages\ViewRecord;

/** Détail d'une entrée d'historique de connexion. */
class ViewLoginHistory extends ViewRecord
{
    protected static string $resource = LoginHistoryResource::class;
}
