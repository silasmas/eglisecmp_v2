<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoSubmissionResource\Pages;

use App\Filament\Resources\GuestInfoSubmissionResource;
use Filament\Resources\Pages\ListRecords;

/** Liste des réponses de fiches. */
class ListGuestInfoSubmissions extends ListRecords
{
    protected static string $resource = GuestInfoSubmissionResource::class;
}
