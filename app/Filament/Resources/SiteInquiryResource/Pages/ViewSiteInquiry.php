<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteInquiryResource\Pages;

use App\Filament\Resources\SiteInquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Détail lecture seule : message complet de la demande publique.
 */
class ViewSiteInquiry extends ViewRecord
{
    protected static string $resource = SiteInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
