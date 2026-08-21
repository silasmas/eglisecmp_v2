<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestInfoSubmissionResource\RelationManagers;

use App\Models\GuestDepartmentNotification;
use App\Models\GuestInfoSubmission;
use App\Models\User;
use App\Services\GuestFormSubmissionService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historique des envois aux départements + accusés de réception.
 */
class DepartmentNotificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'departmentNotifications';

    protected static ?string $title = 'Envois aux départements & accusés';

    protected static ?string $recordTitleAttribute = 'recipient';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('sent_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Département')
                    ->searchable(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        GuestDepartmentNotification::CHANNEL_EMAIL => 'E-mail',
                        GuestDepartmentNotification::CHANNEL_SMS => 'SMS',
                        GuestDepartmentNotification::CHANNEL_WHATSAPP => 'WhatsApp',
                        default => $state ?? '—',
                    })
                    ->badge(),
                TextColumn::make('recipient')->label('Destinataire')->placeholder('—'),
                TextColumn::make('status')
                    ->label('Envoi')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        GuestDepartmentNotification::STATUS_SENT => 'success',
                        GuestDepartmentNotification::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('acknowledged_at')
                    ->label('Accusé')
                    ->boolean()
                    ->getStateUsing(fn (GuestDepartmentNotification $record): bool => $record->acknowledged_at !== null),
                TextColumn::make('acknowledged_at')
                    ->label('Accusé le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En attente'),
                TextColumn::make('acknowledged_by_name')
                    ->label('Par')
                    ->placeholder('—'),
                TextColumn::make('acknowledged_via')
                    ->label('Via')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        GuestDepartmentNotification::ACK_VIA_PORTAL => 'Portail',
                        GuestDepartmentNotification::ACK_VIA_ADMIN => 'Admin',
                        default => '—',
                    })
                    ->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([
                Action::make('resendOne')
                    ->label('Renvoyer')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        CheckboxList::make('channels')
                            ->label('Canaux')
                            ->options([
                                GuestDepartmentNotification::CHANNEL_EMAIL => 'E-mail',
                                GuestDepartmentNotification::CHANNEL_SMS => 'SMS',
                                GuestDepartmentNotification::CHANNEL_WHATSAPP => 'WhatsApp',
                            ])
                            ->default([GuestDepartmentNotification::CHANNEL_EMAIL])
                            ->required()
                            ->columns(3),
                    ])
                    ->action(function (GuestDepartmentNotification $record, array $data): void {
                        /** @var GuestInfoSubmission|null $submission */
                        $submission = $this->getOwnerRecord();
                        $form = $submission?->form;
                        if ($submission === null || $form === null) {
                            Notification::make()->title('Soumission introuvable')->danger()->send();

                            return;
                        }

                        $channels = array_values(array_map('strval', (array) ($data['channels'] ?? [])));
                        $actor = auth()->user() instanceof User ? auth()->user() : null;
                        $result = app(GuestFormSubmissionService::class)->notifyDepartments(
                            $submission,
                            $form,
                            $actor,
                            [(int) $record->church_department_id],
                            $channels,
                        );

                        $body = "Envoyés : {$result['sent']} · Échecs : {$result['failed']} · Ignorés : {$result['skipped']}";
                        if ($result['whatsapp_links'] !== []) {
                            $body .= "\n\nWhatsApp :";
                            foreach ($result['whatsapp_links'] as $link) {
                                $body .= "\n• ".$link['name'].' : '.$link['url'];
                            }
                        }

                        Notification::make()
                            ->title('Renvoi effectué')
                            ->body($body)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('openWhatsApp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (GuestDepartmentNotification $record): ?string => is_string($record->meta['whatsapp_url'] ?? null)
                        ? (string) $record->meta['whatsapp_url']
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (GuestDepartmentNotification $record): bool => $record->channel === GuestDepartmentNotification::CHANNEL_WHATSAPP
                        && filled($record->meta['whatsapp_url'] ?? null)),
                Action::make('markAck')
                    ->label('Marquer reçu')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (GuestDepartmentNotification $record): bool => $record->acknowledged_at === null
                        && $record->status === GuestDepartmentNotification::STATUS_SENT)
                    ->requiresConfirmation()
                    ->action(function (GuestDepartmentNotification $record): void {
                        $record->update([
                            'acknowledged_at' => now(),
                            'acknowledged_by_name' => auth()->user()?->name,
                            'acknowledged_via' => GuestDepartmentNotification::ACK_VIA_ADMIN,
                        ]);

                        Notification::make()
                            ->title('Accusé de réception enregistré')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }
}
