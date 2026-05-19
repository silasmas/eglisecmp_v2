<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteInquiryResource\Pages;

use App\Filament\Resources\SiteInquiryResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste en lecture des demandes issues du formulaire publique.
 */
class ListSiteInquiries extends ListRecords
{
    protected static string $resource = SiteInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
