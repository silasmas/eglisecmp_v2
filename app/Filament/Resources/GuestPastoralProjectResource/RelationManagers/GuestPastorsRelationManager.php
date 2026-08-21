<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\RelationManagers;

use App\Models\GuestPastor;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Suivi pasteurs : invitations et réponses.
 */
class GuestPastorsRelationManager extends RelationManager
{
    protected static string $relationship = 'guestPastors';

    protected static ?string $title = 'Suivi des pasteurs';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('full_name')
            ->columns([
                TextColumn::make('full_name')->label('Pasteur')->searchable(),
                TextColumn::make('email')->label('E-mail')->placeholder('—'),
                TextColumn::make('phone')->label('Tél.')->placeholder('—'),
                TextColumn::make('last_invite')
                    ->label('Dernière invitation')
                    ->state(function (GuestPastor $record): string {
                        $last = $record->inviteDispatches()->orderByDesc('sent_at')->first();
                        if ($last === null) {
                            return 'Jamais envoyé';
                        }

                        $channel = \App\Models\GuestInviteDispatch::channelOptions()[$last->channel] ?? $last->channel;

                        return $channel.' · '.($last->sent_at?->format('d/m/Y H:i') ?? '—');
                    }),
                TextColumn::make('response_status')
                    ->label('Réponse')
                    ->state(fn (GuestPastor $record): string => $record->responseStatusLabel())
                    ->badge()
                    ->color(fn (GuestPastor $record): string => match (true) {
                        $record->form_submitted_at !== null => 'success',
                        $record->form_opened_at !== null => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('form_opened_at')
                    ->label('Ouvert')
                    ->boolean()
                    ->getStateUsing(fn (GuestPastor $record): bool => $record->form_opened_at !== null),
                IconColumn::make('form_submitted_at')
                    ->label('Répondu')
                    ->boolean()
                    ->getStateUsing(fn (GuestPastor $record): bool => $record->form_submitted_at !== null),
                TextColumn::make('form_submitted_at')
                    ->label('Répondu le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
