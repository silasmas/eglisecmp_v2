<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\Pages;

use App\Filament\Resources\GuestPastoralProjectResource;
use App\Models\GuestPastor;
use App\Models\GuestPastoralProject;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/** Édition d’un projet d’accueil. */
class EditGuestPastoralProject extends EditRecord
{
    protected static string $resource = GuestPastoralProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GuestPastoralProjectResource::sendInvitesAction(),
            Action::make('copyLinks')
                ->label('Copier les liens')
                ->icon('heroicon-o-link')
                ->action(function (): void {
                    /** @var GuestPastoralProject $record */
                    $record = $this->getRecord();
                    $lines = $record->guestPastors->map(
                        fn (GuestPastor $p): string => $p->full_name.': '.$p->shortFormUrl()
                    )->implode("\n");

                    Notification::make()
                        ->title('Liens courts des invitations')
                        ->body($lines !== '' ? $lines : 'Aucun pasteur invité.')
                        ->success()
                        ->persistent()
                        ->send();
                }),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
