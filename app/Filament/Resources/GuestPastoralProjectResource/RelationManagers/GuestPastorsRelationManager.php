<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\RelationManagers;

use App\Models\ChurchWorker;
use App\Models\GuestEventOutfit;
use App\Models\GuestInfoSubmission;
use App\Models\GuestPastor;
use App\Models\User;
use App\Services\GuestPortalDispatchService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Suivi pasteurs + jours d’intervention + équipe de service + renvoi portail.
 */
class GuestPastorsRelationManager extends RelationManager
{
    protected static string $relationship = 'guestPastors';

    protected static ?string $title = 'Suivi des pasteurs';

    protected static ?string $recordTitleAttribute = 'full_name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                TextInput::make('full_name')->label('Nom')->disabled()->columnSpan(6),
                TextInput::make('email')->label('E-mail')->disabled()->columnSpan(3),
                TextInput::make('phone')->label('Tél.')->disabled()->columnSpan(3),
                Repeater::make('assignments')
                    ->relationship()
                    ->label('Jours d’intervention')
                    ->columnSpanFull()
                    ->orderColumn('sort_order')
                    ->schema([
                        DatePicker::make('day_date')->label('Date')->required()->columnSpan(3),
                        Select::make('session_key')
                            ->label('Session')
                            ->options(GuestEventOutfit::sessionOptions())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $labels = [
                                    'matin' => 'Matin',
                                    'midi' => 'Midi',
                                    'soir' => 'Soir',
                                    'samedi' => 'Samedi',
                                ];
                                if (is_string($state) && isset($labels[$state])) {
                                    $set('label', $labels[$state]);
                                }
                            })
                            ->columnSpan(3),
                        TextInput::make('label')->label('Libellé')->required()->columnSpan(2),
                        TextInput::make('color')->label('Couleur')->type('color')->default('#7b1d3e')->columnSpan(2),
                        TextInput::make('location')->label('Lieu')->columnSpan(2),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->addActionLabel('Ajouter un jour'),
                Repeater::make('assignedWorkers')
                    ->relationship()
                    ->label('Équipe de service')
                    ->columnSpanFull()
                    ->orderColumn('sort_order')
                    ->schema([
                        Select::make('church_worker_id')
                            ->label('Ouvrier')
                            ->options(fn (): array => ChurchWorker::query()
                                ->where('status', ChurchWorker::STATUS_APPROVED)
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (ChurchWorker $w): array => [
                                    $w->id => $w->fullName().' — '.($w->department?->name ?? '—'),
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $worker = ChurchWorker::query()->with('department')->find($state);
                                if ($worker !== null) {
                                    $set('department_id', $worker->department_id);
                                    $set('display_title', $worker->department?->name);
                                }
                            })
                            ->columnSpan(5),
                        Select::make('department_id')
                            ->label('Département')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(3),
                        TextInput::make('display_title')
                            ->label('Titre affiché (groupe)')
                            ->helperText('Ex. : Porteurs d’Armes')
                            ->columnSpan(4),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->addActionLabel('Ajouter un ouvrier'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('full_name')
            ->columns([
                TextColumn::make('full_name')->label('Pasteur')->searchable(),
                TextColumn::make('email')->label('E-mail')->placeholder('—'),
                TextColumn::make('phone')->label('Tél.')->placeholder('—'),
                TextColumn::make('assignments_count')->counts('assignments')->label('Jours'),
                TextColumn::make('assigned_workers_count')->counts('assignedWorkers')->label('Équipe'),
                TextColumn::make('response_status')
                    ->label('Réponse')
                    ->state(fn (GuestPastor $record): string => $record->responseStatusLabel())
                    ->badge()
                    ->color(fn (GuestPastor $record): string => match (true) {
                        $record->form_submitted_at !== null => 'success',
                        $record->form_opened_at !== null => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('form_submitted_at')
                    ->label('Répondu')
                    ->boolean()
                    ->getStateUsing(fn (GuestPastor $record): bool => $record->form_submitted_at !== null),
            ])
            ->headerActions([])
            ->actions([
                EditAction::make()
                    ->label('Jours & équipe')
                    ->modalHeading(fn (GuestPastor $record): string => 'Planning & équipe — '.$record->full_name)
                    ->modalWidth('5xl'),
                Action::make('resendPortal')
                    ->label('Renvoyer portail')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (GuestPastor $record): bool => $record->submission !== null)
                    ->form([
                        CheckboxList::make('channels')
                            ->label('Canaux')
                            ->options([
                                'email' => 'E-mail',
                                'sms' => 'SMS',
                                'whatsapp' => 'WhatsApp',
                            ])
                            ->default(['email'])
                            ->required()
                            ->columns(3),
                    ])
                    ->action(function (GuestPastor $record, array $data): void {
                        $submission = $record->submission;
                        if (! $submission instanceof GuestInfoSubmission) {
                            Notification::make()->title('Aucune soumission')->warning()->send();

                            return;
                        }

                        $actor = auth()->user() instanceof User ? auth()->user() : null;
                        $result = app(GuestPortalDispatchService::class)->dispatch(
                            $submission,
                            array_values($data['channels'] ?? ['email']),
                            $actor,
                        );

                        Notification::make()
                            ->title('Lien portail traité')
                            ->body(sprintf(
                                'OK : %d · échecs : %d · ignorés : %d',
                                $result['sent'],
                                $result['failed'],
                                $result['skipped'],
                            ).($result['messages'] !== [] ? "\n".implode("\n", array_slice($result['messages'], 0, 5)) : ''))
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }
}
