<?php

declare(strict_types=1);

namespace App\Filament\Resources\MinisterReceptionScheduleResource\Pages;

use App\Filament\Resources\MinisterReceptionScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMinisterReceptionSchedule extends CreateRecord
{
    protected static string $resource = MinisterReceptionScheduleResource::class;

    /**
     * Valide l’unicité du créneau par bureau avant création.
     *
     * @param  array<string, mixed>  $data  Données du formulaire.
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        MinisterReceptionScheduleResource::assertBureauSlotAvailable($data);

        return $data;
    }
}
