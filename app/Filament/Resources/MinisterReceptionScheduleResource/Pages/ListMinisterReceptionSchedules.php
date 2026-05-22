<?php

declare(strict_types=1);

namespace App\Filament\Resources\MinisterReceptionScheduleResource\Pages;

use App\Filament\Resources\MinisterReceptionScheduleResource;
use App\Models\MinisterReceptionSchedule;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des horaires de réception pasteurs avec rappel bureau / RDV public.
 */
class ListMinisterReceptionSchedules extends ListRecords
{
    protected static string $resource = MinisterReceptionScheduleResource::class;

    public function getSubheading(): ?string
    {
        $missingBureauCount = MinisterReceptionSchedule::query()
            ->where('is_active', true)
            ->whereNull('bureau_id')
            ->count();

        if ($missingBureauCount === 0) {
            return 'Seuls les pasteurs avec un horaire actif lié à un bureau sont proposés sur la prise de RDV en ligne.';
        }

        return $missingBureauCount.' horaire(s) actif(s) sans bureau : le pasteur concerné n’apparaît pas sur la prise de RDV tant qu’un bureau n’est pas affecté.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
