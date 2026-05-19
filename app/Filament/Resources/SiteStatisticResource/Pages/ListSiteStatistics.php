<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteStatisticResource\Pages;

use App\Filament\Resources\SiteStatisticResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSiteStatistics extends ListRecords
{
    protected static string $resource = SiteStatisticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
