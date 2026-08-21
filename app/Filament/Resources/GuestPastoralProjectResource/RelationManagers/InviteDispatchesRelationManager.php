<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\RelationManagers;

use App\Models\GuestInviteDispatch;
use App\Models\GuestPastor;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Historique des invitations envoyées (e-mail / SMS / WhatsApp) pour un projet.
 */
class InviteDispatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'inviteDispatches';

    protected static ?string $title = 'Historique des invitations';

    protected static ?string $recordTitleAttribute = 'channel';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('sent_at')
                    ->label('Envoyé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('guestPastor.full_name')
                    ->label('Pasteur')
                    ->searchable(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->formatStateUsing(fn (?string $state): string => GuestInviteDispatch::channelOptions()[$state] ?? ($state ?? '—'))
                    ->badge(),
                TextColumn::make('recipient')->label('Destinataire')->placeholder('—'),
                TextColumn::make('status')
                    ->label('Statut envoi')
                    ->formatStateUsing(fn (?string $state): string => GuestInviteDispatch::statusOptions()[$state] ?? ($state ?? '—'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        GuestInviteDispatch::STATUS_SENT, GuestInviteDispatch::STATUS_LINK_READY => 'success',
                        GuestInviteDispatch::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('response_status')
                    ->label('Réponse pasteur')
                    ->state(function (GuestInviteDispatch $record): string {
                        $pastor = $record->guestPastor;
                        if (! $pastor instanceof GuestPastor) {
                            return '—';
                        }

                        return $pastor->responseStatusLabel();
                    })
                    ->badge()
                    ->color(fn (GuestInviteDispatch $record): string => match (true) {
                        $record->guestPastor?->form_submitted_at !== null => 'success',
                        $record->guestPastor?->form_opened_at !== null => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('guestPastor.form_submitted_at')
                    ->label('Répondu le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('sentBy.name')
                    ->label('Par')
                    ->placeholder('Système'),
            ])
            ->headerActions([])
            ->actions([
                \Filament\Actions\Action::make('openWhatsApp')
                    ->label('Ouvrir WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (GuestInviteDispatch $record): ?string => is_string($record->meta['whatsapp_url'] ?? null)
                        ? (string) $record->meta['whatsapp_url']
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (GuestInviteDispatch $record): bool => $record->channel === GuestInviteDispatch::CHANNEL_WHATSAPP
                        && filled($record->meta['whatsapp_url'] ?? null)),
            ])
            ->bulkActions([]);
    }
}
