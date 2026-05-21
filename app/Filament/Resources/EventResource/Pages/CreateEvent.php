<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\Event;
use App\Support\EventFeaturedGuard;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    /**
     * Valide la fenêtre de mise en avant avant création.
     *
     * @param  array<string, mixed>  $data  Données du formulaire.
     * @return array<string, mixed> Données inchangées si valides.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if ($data['est_a_la_une'] ?? false) {
            $preview = new Event($data);
            EventFeaturedGuard::assertFeaturedWindowValid($preview);
        }

        return $data;
    }

    /**
     * Applique les règles de mise en avant unique après création.
     */
    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if (! $record->est_a_la_une) {
            return;
        }

        EventFeaturedGuard::ensureSingleFeaturedExcept($record);
    }
}
