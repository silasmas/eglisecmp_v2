<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Support\EventFeaturedGuard;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $state = $this->form->getState();
        $this->record->fill($state);

        if ($this->record->est_a_la_une) {
            EventFeaturedGuard::assertFeaturedWindowValid($this->record);
        }
    }

    /**
     * Applique les règles de mise en avant unique après enregistrement.
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if (! $record->est_a_la_une) {
            return;
        }

        EventFeaturedGuard::assertFeaturedWindowValid($record);
        EventFeaturedGuard::ensureSingleFeaturedExcept($record);
    }
}
