<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchCellResource\Pages;

use App\Filament\Resources\ChurchCellResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d’une cellule.
 */
class CreateChurchCell extends CreateRecord
{
    protected static string $resource = ChurchCellResource::class;
}
