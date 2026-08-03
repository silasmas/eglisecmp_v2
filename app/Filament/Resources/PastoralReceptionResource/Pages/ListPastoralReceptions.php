<?php

declare(strict_types=1);

namespace App\Filament\Resources\PastoralReceptionResource\Pages;

use App\Filament\Resources\PastoralReceptionResource;
use App\Filament\Widgets\PastoralAppointmentStatsOverviewWidget;
use App\Models\SiteInquiry;
use App\Support\PastoralAccess;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/** Liste des dossiers de réception pastorale. */
class ListPastoralReceptions extends ListRecords
{
    protected static string $resource = PastoralReceptionResource::class;

    /**
     * Masque les dossiers clôturés pour les pasteurs non titulaires (voir Historique).
     */
    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();
        if ($query === null) {
            return null;
        }

        if (! PastoralAccess::canViewAllAppointments(auth()->user())) {
            $query->where(function (Builder $q): void {
                $q->whereNull('dossier_status')
                    ->orWhere('dossier_status', '!=', SiteInquiry::DOSSIER_CLOSED);
            });
        }

        return $query;
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            PastoralAppointmentStatsOverviewWidget::class,
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('history')
                ->label('Historique RDV')
                ->icon('heroicon-o-clock')
                ->url(PastoralReceptionResource::getUrl('history'))
                ->color('gray'),
        ];
    }
}
