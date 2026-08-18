<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoSubmissionResource\Pages;

use App\Filament\Resources\GuestInfoSubmissionResource;
use Filament\Resources\Pages\ViewRecord;

/** Détail d’une soumission (filtrée selon les droits). */
class ViewGuestInfoSubmission extends ViewRecord
{
    protected static string $resource = GuestInfoSubmissionResource::class;
}
