<?php

declare(strict_types=1);

namespace App\Filament\Resources\WorshipServiceReportResource\Pages;

use App\Filament\Resources\WorshipServiceReportResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création manuelle d'un rapport de culte (admin).
 */
class CreateWorshipServiceReport extends CreateRecord
{
    protected static string $resource = WorshipServiceReportResource::class;
}
