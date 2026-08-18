<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoFormResource\Pages;

use App\Filament\Resources\GuestInfoFormResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/** Liste des formulaires d’accueil. */
class ListGuestInfoForms extends ListRecords
{
    protected static string $resource = GuestInfoFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
