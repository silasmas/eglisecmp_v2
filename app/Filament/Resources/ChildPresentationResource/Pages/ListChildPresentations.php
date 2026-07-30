<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChildPresentationResource\Pages;

use App\Filament\Resources\ChildPresentationResource;
use App\Filament\Widgets\ChildPresentationStatsOverviewWidget;
use App\Models\ChildPresentation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste des demandes de présentation d'enfants avec onglets de statut.
 */
class ListChildPresentations extends ListRecords
{
    protected static string $resource = ChildPresentationResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes')
                ->badge(fn (): int => ChildPresentation::query()->count()),
            'pending' => Tab::make('En attente')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', ChildPresentation::STATUS_PENDING),
                )
                ->badge(
                    fn (): int => ChildPresentation::query()
                        ->where('status', ChildPresentation::STATUS_PENDING)
                        ->count(),
                ),
            'confirmed' => Tab::make('Confirmées')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', ChildPresentation::STATUS_CONFIRMED),
                )
                ->badge(
                    fn (): int => ChildPresentation::query()
                        ->where('status', ChildPresentation::STATUS_CONFIRMED)
                        ->count(),
                ),
            'declined' => Tab::make('Refusées')
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where('status', ChildPresentation::STATUS_DECLINED),
                )
                ->badge(
                    fn (): int => ChildPresentation::query()
                        ->where('status', ChildPresentation::STATUS_DECLINED)
                        ->count(),
                ),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ChildPresentationStatsOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
