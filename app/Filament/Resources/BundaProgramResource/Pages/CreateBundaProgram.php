<?php

declare(strict_types=1);

namespace App\Filament\Resources\BundaProgramResource\Pages;

use App\Filament\Resources\BundaProgramResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d’un programme Bunda.
 */
class CreateBundaProgram extends CreateRecord
{
    protected static string $resource = BundaProgramResource::class;
}
