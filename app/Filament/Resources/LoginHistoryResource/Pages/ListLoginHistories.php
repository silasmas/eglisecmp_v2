<?php

declare(strict_types=1);

namespace App\Filament\Resources\LoginHistoryResource\Pages;

use App\Filament\Resources\LoginHistoryResource;
use Filament\Resources\Pages\ListRecords;

/** Liste de l'historique des connexions. */
class ListLoginHistories extends ListRecords
{
    protected static string $resource = LoginHistoryResource::class;
}
