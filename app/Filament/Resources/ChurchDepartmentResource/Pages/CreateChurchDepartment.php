<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChurchDepartmentResource\Pages;

use App\Filament\Resources\ChurchDepartmentResource;
use Filament\Resources\Pages\CreateRecord;

/** Création département. */
class CreateChurchDepartment extends CreateRecord
{
    protected static string $resource = ChurchDepartmentResource::class;
}
