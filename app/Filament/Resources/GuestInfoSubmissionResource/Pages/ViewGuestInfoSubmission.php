<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoSubmissionResource\Pages;

use App\Filament\Resources\GuestInfoSubmissionResource;
use App\Models\GuestInfoSubmission;
use App\Models\User;
use App\Services\GuestFormSubmissionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/** Détail d’une soumission (filtrée selon les droits). */
class ViewGuestInfoSubmission extends ViewRecord
{
    protected static string $resource = GuestInfoSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resendDepartments')
                ->label('Renvoyer aux départements')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Renvoyer les réponses aux départements ?')
                ->modalDescription('Un nouvel e-mail sera envoyé à chaque département concerné et l’historique sera mis à jour.')
                ->action(function (): void {
                    /** @var GuestInfoSubmission $record */
                    $record = $this->getRecord();
                    $form = $record->form;
                    if ($form === null) {
                        Notification::make()->title('Formulaire introuvable')->danger()->send();

                        return;
                    }

                    $actor = auth()->user() instanceof User ? auth()->user() : null;
                    $result = app(GuestFormSubmissionService::class)->notifyDepartments($record, $form, $actor);

                    Notification::make()
                        ->title('Notifications départements')
                        ->body("Envoyés : {$result['sent']} · Échecs : {$result['failed']} · Ignorés : {$result['skipped']}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
