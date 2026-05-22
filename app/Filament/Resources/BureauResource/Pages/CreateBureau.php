<?php

declare(strict_types=1);

namespace App\Filament\Resources\BureauResource\Pages;

use App\Filament\Resources\BureauResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d’un bureau de réception.
 */
class CreateBureau extends CreateRecord
{
    protected static string $resource = BureauResource::class;
}
