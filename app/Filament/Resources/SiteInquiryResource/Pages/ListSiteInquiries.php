<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteInquiryResource\Pages;

use App\Filament\Resources\SiteInquiryResource;
use App\Models\SiteInquiry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste en lecture des demandes issues du formulaire publique.
 */
class ListSiteInquiries extends ListRecords
{
    protected static string $resource = SiteInquiryResource::class;

    /**
     * @return array<string, Tab> Onglets filtrant prière, rendez-vous ou tout afficher.
     */
    public function getTabs(): array
    {
        return [
            SiteInquiryResource::LIST_TAB_ALL => Tab::make('Toutes les demandes')
                ->icon('heroicon-o-inbox')
                ->badge(
                    fn (): int => SiteInquiry::query()->count(),
                ),
            SiteInquiryResource::LIST_TAB_PRAYER => Tab::make('Requêtes de prière')
                ->icon('heroicon-o-heart')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('kind', SiteInquiry::KIND_PRAYER),
                )
                ->badge(
                    fn (): int => SiteInquiry::query()->where('kind', SiteInquiry::KIND_PRAYER)->count(),
                ),
            SiteInquiryResource::LIST_TAB_APPOINTMENT => Tab::make('Demandes de RDV')
                ->icon('heroicon-o-calendar-days')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('kind', SiteInquiry::KIND_APPOINTMENT),
                )
                ->badge(
                    fn (): int => SiteInquiry::query()->where('kind', SiteInquiry::KIND_APPOINTMENT)->count(),
                ),
        ];
    }

    /**
     * @return string Onglet affiché par défaut à l’ouverture de la page.
     */
    public function getDefaultActiveTab(): string
    {
        return SiteInquiryResource::LIST_TAB_ALL;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
