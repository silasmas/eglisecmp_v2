<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoSubmissionResource\Pages;

use App\Filament\Resources\GuestInfoSubmissionResource;
use App\Models\ChurchDepartment;
use App\Models\GuestDepartmentNotification;
use App\Models\GuestInfoSubmission;
use App\Models\User;
use App\Services\GuestFormSubmissionService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;

/** Détail d’une soumission (filtrée selon les droits). */
class ViewGuestInfoSubmission extends ViewRecord
{
    protected static string $resource = GuestInfoSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resendDepartments')
                ->label('Renvoyer le lien')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->modalHeading('Renvoyer les réponses aux départements')
                ->modalDescription('Choisissez les départements et les canaux (e-mail, SMS, WhatsApp).')
                ->modalWidth('xl')
                ->form(function (): array {
                    /** @var GuestInfoSubmission $record */
                    $record = $this->getRecord();
                    $service = app(GuestFormSubmissionService::class);
                    $ids = $service->departmentIdsForSubmission($record);
                    $options = ChurchDepartment::query()
                        ->whereIn('id', $ids)
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(function (ChurchDepartment $dept): array {
                            $contact = collect([$dept->contact_email, $dept->contact_phone])->filter()->implode(' · ');

                            return [
                                $dept->id => $dept->name.($contact !== '' ? ' ('.$contact.')' : ' — sans contact'),
                            ];
                        })
                        ->all();

                    return [
                        Radio::make('recipient_mode')
                            ->label('Destinataires')
                            ->options([
                                'all' => 'Tous les départements concernés',
                                'selected' => 'Sélectionner certains départements',
                            ])
                            ->default('all')
                            ->live()
                            ->required(),
                        CheckboxList::make('department_ids')
                            ->label('Départements')
                            ->options($options)
                            ->columns(1)
                            ->required()
                            ->visible(fn (Get $get): bool => $get('recipient_mode') === 'selected'),
                        CheckboxList::make('channels')
                            ->label('Canaux')
                            ->options([
                                GuestDepartmentNotification::CHANNEL_EMAIL => 'E-mail',
                                GuestDepartmentNotification::CHANNEL_SMS => 'SMS',
                                GuestDepartmentNotification::CHANNEL_WHATSAPP => 'WhatsApp',
                            ])
                            ->default([GuestDepartmentNotification::CHANNEL_EMAIL])
                            ->required()
                            ->columns(3)
                            ->helperText('SMS/WhatsApp utilisent le téléphone du département. WhatsApp ouvre un lien wa.me à valider.'),
                    ];
                })
                ->action(function (array $data): void {
                    /** @var GuestInfoSubmission $record */
                    $record = $this->getRecord();
                    $form = $record->form;
                    if ($form === null) {
                        Notification::make()->title('Formulaire introuvable')->danger()->send();

                        return;
                    }

                    $mode = (string) ($data['recipient_mode'] ?? 'all');
                    $deptIds = $mode === 'selected'
                        ? array_map('intval', (array) ($data['department_ids'] ?? []))
                        : null;
                    $channels = array_values(array_map('strval', (array) ($data['channels'] ?? [])));

                    if ($mode === 'selected' && ($deptIds === null || $deptIds === [])) {
                        Notification::make()->title('Aucun département sélectionné')->danger()->send();

                        return;
                    }

                    $actor = auth()->user() instanceof User ? auth()->user() : null;
                    $result = app(GuestFormSubmissionService::class)->notifyDepartments(
                        $record,
                        $form,
                        $actor,
                        $deptIds,
                        $channels,
                    );

                    $body = "Envoyés : {$result['sent']} · Échecs : {$result['failed']} · Ignorés : {$result['skipped']}";
                    if ($result['whatsapp_links'] !== []) {
                        $body .= "\n\nLiens WhatsApp :";
                        foreach ($result['whatsapp_links'] as $link) {
                            $body .= "\n• ".$link['name'].' : '.$link['url'];
                        }
                    }

                    Notification::make()
                        ->title('Notifications départements')
                        ->body($body)
                        ->success()
                        ->persistent()
                        ->send();
                }),
        ];
    }
}
