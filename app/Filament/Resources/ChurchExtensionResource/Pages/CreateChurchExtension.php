<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchExtensionResource\Pages;

use App\Filament\Resources\ChurchExtensionResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'une extension CMP.
 */
class CreateChurchExtension extends CreateRecord
{
    protected static string $resource = ChurchExtensionResource::class;
}
