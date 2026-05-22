<?php

declare(strict_types=1);

namespace App\Filament\Resources\MinisterReceptionScheduleResource\Pages;

use App\Filament\Resources\MinisterReceptionScheduleResource;
use Filament\Resources\Pages\EditRecord;

class EditMinisterReceptionSchedule extends EditRecord
{
    protected static string $resource = MinisterReceptionScheduleResource::class;

    /**
     * Valide l’unicité du créneau par bureau avant mise à jour.
     *
     * @param  array<string, mixed>  $data  Données du formulaire.
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        MinisterReceptionScheduleResource::assertBureauSlotAvailable($data, $this->getRecord()->getKey());

        return $data;
    }
}
