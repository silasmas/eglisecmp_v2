<?php

declare(strict_types=1);

namespace App\Filament\Resources\YoutubeSyncRunResource\Pages;

use App\Filament\Resources\YoutubeSyncRunResource;
use Filament\Resources\Pages\ViewRecord;

/**
 * Détail d’une synchronisation YouTube (message, erreur, statistiques).
 */
class ViewYoutubeSyncRun extends ViewRecord
{
    protected static string $resource = YoutubeSyncRunResource::class;
}
