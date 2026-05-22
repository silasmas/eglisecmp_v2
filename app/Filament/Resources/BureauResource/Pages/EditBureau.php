<?php

declare(strict_types=1);

namespace App\Filament\Resources\BureauResource\Pages;

use App\Filament\Resources\BureauResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d’un bureau de réception.
 */
class EditBureau extends EditRecord
{
    protected static string $resource = BureauResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => BureauResource::canDeleteRecord($this->getRecord()))
                ->before(function (DeleteAction $action): void {
                    if (! BureauResource::canDeleteRecord($this->getRecord())) {
                        Notification::make()
                            ->title('Suppression impossible')
                            ->body('Ce bureau est utilisé par des horaires pasteurs ou des rendez-vous.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
