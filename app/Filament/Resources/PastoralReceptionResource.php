<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\ProvidesAdminTourStep;
use App\Filament\Resources\PastoralReceptionResource\Pages;
use App\Filament\Widgets\PastoralAppointmentStatsOverviewWidget;
use App\Models\Minister;
use App\Models\SiteInquiry;
use App\Models\User;
use App\Services\PastoralSessionService;
use App\Services\PastoralTransferNotificationService;
use App\Support\AppointmentReasons;
use App\Support\PastoralAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Module pasteurs : réception des fidèles, chrono, clôture, orientation, historique.
 */
class PastoralReceptionResource extends Resource
{
    use HasTabbedActions;
    use ProvidesAdminTourStep;

    protected static ?string $model = SiteInquiry::class;

    protected static ?string $navigationLabel = 'Réception pastorale';

    protected static ?string $modelLabel = 'Dossier RDV';

    protected static ?string $pluralModelLabel = 'Réception pastorale';

    protected static ?string $slug = 'pastoral-reception';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Pastoral';

    protected static ?int $navigationSort = 5;

    /**
     * Visible pour les comptes pasteurs liés ou les admins Shield.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('ViewAny:SiteInquiry') || $user->hasRole('super_admin')) {
            return true;
        }

        return PastoralAccess::linkedMinister($user) !== null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Consultation : pasteur assigné, titulaire ou super_admin uniquement.
     */
    public static function canView(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof SiteInquiry
            && PastoralAccess::canAccessDossier($user instanceof User ? $user : null, $record);
    }

    /**
     * Édition : dossier non clos (sauf droits titulaire après réouverture).
     */
    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $record instanceof SiteInquiry
            && PastoralAccess::canEditDossier($user instanceof User ? $user : null, $record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('kind', SiteInquiry::KIND_APPOINTMENT)
            ->with(['minister', 'orientedFromMinister', 'bureau']);

        $user = auth()->user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        $scopedId = PastoralAccess::scopedMinisterId($user);
        if ($scopedId === 0) {
            return $query->whereRaw('1 = 0');
        }
        if ($scopedId !== null) {
            $query->where('minister_id', $scopedId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Fidèle')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nom')->disabled()->columnSpan(4),
                        TextInput::make('phone')->label('Téléphone')->disabled()->columnSpan(4),
                        TextInput::make('email')->label('E-mail')->disabled()->columnSpan(4),
                        Textarea::make('message')->label('Motif initial (fidèle)')->disabled()->rows(3)->columnSpanFull(),
                    ]),
                Section::make('Dossier pastoral')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('appointment_reason')
                            ->label('Classification / motif')
                            ->options(AppointmentReasons::options())
                            ->searchable()
                            ->columnSpan(4),
                        Select::make('reception_status')
                            ->label('Statut réception')
                            ->options(SiteInquiry::receptionStatusOptions())
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('session_duration_minutes')
                            ->label('Durée session (min)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(240)
                            ->helperText('Chrono démarré à « Marquer reçu ». Ajustable par le pasteur.')
                            ->columnSpan(4),
                        DateTimePicker::make('preferred_at')
                            ->label('Créneau')
                            ->disabled()
                            ->columnSpan(4),
                        DateTimePicker::make('next_appointment_at')
                            ->label('Prochain RDV')
                            ->disabled()
                            ->columnSpan(4),
                        Textarea::make('session_notes')
                            ->label('Notes de réception (ce qui a été dit)')
                            ->rows(6)
                            ->columnSpanFull(),
                        Textarea::make('session_conclusion')
                            ->label('Conclusion pastorale')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Fidèle')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')->label('Nom')->columnSpan(4),
                        TextEntry::make('phone')->label('Téléphone')->columnSpan(4),
                        TextEntry::make('email')->label('E-mail')->placeholder('—')->columnSpan(4),
                        TextEntry::make('preferred_at')->label('Créneau')->dateTime('d/m/Y H:i')->columnSpan(4),
                        TextEntry::make('minister.fullname')
                            ->label('Pasteur assigné')
                            ->formatStateUsing(fn ($state): string => MinisterResource::normalizeLegacyValue($state) ?? '—')
                            ->columnSpan(4),
                        TextEntry::make('bureau.name')->label('Bureau')->placeholder('—')->columnSpan(4),
                        TextEntry::make('message')->label('Motif initial')->columnSpanFull(),
                    ]),
                Section::make('Dossier')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('appointment_reason')
                            ->label('Classification')
                            ->formatStateUsing(fn (?string $state): string => AppointmentReasons::label($state))
                            ->columnSpan(3),
                        TextEntry::make('dossier_status')
                            ->label('Dossier')
                            ->formatStateUsing(fn (?string $state): string => SiteInquiry::dossierStatusOptions()[$state] ?? ($state ?? '—'))
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                SiteInquiry::DOSSIER_CLOSED => 'gray',
                                SiteInquiry::DOSSIER_SUSPENDED => 'danger',
                                SiteInquiry::DOSSIER_FOLLOW_UP => 'warning',
                                default => 'success',
                            })
                            ->columnSpan(3),
                        TextEntry::make('reception_status')
                            ->label('Réception')
                            ->formatStateUsing(fn (?string $state): string => SiteInquiry::receptionStatusOptions()[$state] ?? ($state ?? '—'))
                            ->columnSpan(3),
                        TextEntry::make('received_at')
                            ->label('Reçu le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non reçu')
                            ->columnSpan(3),
                        TextEntry::make('session_duration_minutes')
                            ->label('Durée prévue')
                            ->suffix(' min')
                            ->placeholder('—')
                            ->columnSpan(3),
                        TextEntry::make('time_respected')
                            ->label('Temps respecté')
                            ->formatStateUsing(fn (?bool $state): string => match ($state) {
                                true => 'Oui',
                                false => 'Non (dépassement)',
                                default => '—',
                            })
                            ->badge()
                            ->color(fn (?bool $state): string => match ($state) {
                                true => 'success',
                                false => 'danger',
                                default => 'gray',
                            })
                            ->columnSpan(3),
                        TextEntry::make('next_appointment_at')
                            ->label('Prochain RDV')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—')
                            ->columnSpan(3),
                        TextEntry::make('session_notes')->label('Notes')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('session_conclusion')->label('Conclusion')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('orientedFromMinister.fullname')
                            ->label('Orienté par')
                            ->visible(fn (SiteInquiry $record): bool => $record->oriented_from_minister_id !== null)
                            ->formatStateUsing(fn ($state): string => MinisterResource::normalizeLegacyValue($state) ?? '—')
                            ->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('preferred_at', 'desc')
            ->columns([
                TextColumn::make('preferred_at')->label('Créneau')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('name')->label('Fidèle')->searchable(),
                TextColumn::make('appointment_reason')
                    ->label('Motif')
                    ->formatStateUsing(fn (?string $state): string => AppointmentReasons::label($state))
                    ->badge(),
                TextColumn::make('minister.fullname')
                    ->label('Pasteur')
                    ->formatStateUsing(fn ($state): string => MinisterResource::normalizeLegacyValue($state) ?? '—')
                    ->toggleable()
                    ->visible(fn (): bool => PastoralAccess::canViewAllAppointments(auth()->user())),
                TextColumn::make('dossier_status')
                    ->label('Dossier')
                    ->formatStateUsing(fn (?string $state): string => SiteInquiry::dossierStatusOptions()[$state ?? SiteInquiry::DOSSIER_OPEN] ?? '—')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        SiteInquiry::DOSSIER_CLOSED => 'gray',
                        SiteInquiry::DOSSIER_SUSPENDED => 'danger',
                        SiteInquiry::DOSSIER_FOLLOW_UP => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('reception_status')
                    ->label('Réception')
                    ->formatStateUsing(fn (?string $state): string => SiteInquiry::receptionStatusOptions()[$state] ?? '—')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        SiteInquiry::RECEPTION_COMPLETED => 'success',
                        SiteInquiry::RECEPTION_IN_PROGRESS => 'warning',
                        SiteInquiry::RECEPTION_ORIENTED => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('time_respected')
                    ->label('Temps')
                    ->formatStateUsing(fn (?bool $state): string => match ($state) {
                        true => 'OK',
                        false => 'Dépassé',
                        default => '—',
                    })
                    ->badge()
                    ->color(fn (?bool $state): string => match ($state) {
                        true => 'success',
                        false => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('received_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Non reçu')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('appointment_reason')
                    ->label('Motif')
                    ->options(AppointmentReasons::options()),
                SelectFilter::make('dossier_status')
                    ->label('Dossier')
                    ->options(SiteInquiry::dossierStatusOptions()),
                SelectFilter::make('reception_status')
                    ->label('Réception')
                    ->options(SiteInquiry::receptionStatusOptions()),
                SelectFilter::make('minister_id')
                    ->label('Pasteur')
                    ->options(fn (): array => Minister::query()
                        ->where('is_active', true)
                        ->orderBy('fullname')
                        ->pluck('fullname', 'id')
                        ->mapWithKeys(fn ($name, $id) => [$id => MinisterResource::normalizeLegacyValue($name) ?? (string) $id])
                        ->all())
                    ->visible(fn (): bool => PastoralAccess::canViewAllAppointments(auth()->user())),
                Filter::make('today')
                    ->label('Aujourd’hui')
                    ->query(fn (Builder $query): Builder => $query->whereDate('preferred_at', today())),
                Filter::make('this_week')
                    ->label('Cette semaine')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('preferred_at', [now()->startOfWeek(), now()->endOfWeek()])),
                Filter::make('overruns')
                    ->label('Temps dépassé')
                    ->query(fn (Builder $query): Builder => $query->where('time_respected', false)),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->label('Dossier')
                    ->visible(fn (SiteInquiry $record): bool => static::canEdit($record)),
                Action::make('markReceived')
                    ->label('Marquer reçu')
                    ->icon('heroicon-o-hand-raised')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Accuser réception du fidèle')
                    ->modalDescription('Le chrono de séance démarre immédiatement selon la durée du créneau.')
                    ->visible(fn (SiteInquiry $record): bool => $record->received_at === null
                        && ! PastoralAccess::isDossierClosed($record)
                        && PastoralAccess::canMarkReceived(
                            auth()->user() instanceof User ? auth()->user() : null,
                            (int) ($record->minister_id ?? 0),
                        ))
                    ->action(function (SiteInquiry $record): void {
                        app(PastoralSessionService::class)->markReceived($record);

                        Notification::make()
                            ->title('Réception accusée')
                            ->body('Chrono démarré. Gérez le temps dans le dossier.')
                            ->success()
                            ->send();
                    }),
                Action::make('suspendDossier')
                    ->label('Suspendre')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (SiteInquiry $record): bool => static::canEdit($record)
                        && ($record->dossier_status ?? SiteInquiry::DOSSIER_OPEN) === SiteInquiry::DOSSIER_OPEN
                        && $record->received_at !== null)
                    ->action(function (SiteInquiry $record): void {
                        app(PastoralSessionService::class)->suspend($record);
                        Notification::make()->title('Dossier suspendu')->warning()->send();
                    }),
                Action::make('closeDossier')
                    ->label('Clôturer')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clôturer le dossier')
                    ->modalDescription('Le respect du temps sera enregistré. Seul le pasteur titulaire pourra réouvrir.')
                    ->visible(fn (SiteInquiry $record): bool => static::canEdit($record)
                        && ! PastoralAccess::isDossierClosed($record)
                        && $record->received_at !== null)
                    ->action(function (SiteInquiry $record): void {
                        app(PastoralSessionService::class)->close($record);
                        $ok = $record->fresh()?->time_respected;
                        Notification::make()
                            ->title('Dossier clôturé')
                            ->body($ok === false
                                ? 'Temps dépassé — noté dans l’historique (point à améliorer).'
                                : 'Temps respecté enregistré.')
                            ->success()
                            ->send();
                    }),
                Action::make('scheduleNext')
                    ->label('Prochain RDV')
                    ->icon('heroicon-o-calendar-days')
                    ->color('warning')
                    ->visible(fn (SiteInquiry $record): bool => static::canEdit($record)
                        && $record->received_at !== null
                        && ! PastoralAccess::isDossierClosed($record))
                    ->form([
                        DateTimePicker::make('next_appointment_at')
                            ->label('Date du prochain rendez-vous')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                        Textarea::make('follow_up_note')
                            ->label('Note de suivi')
                            ->rows(2),
                    ])
                    ->action(function (SiteInquiry $record, array $data): void {
                        $note = trim((string) ($data['follow_up_note'] ?? ''));
                        if ($note !== '') {
                            $existing = trim((string) ($record->session_notes ?? ''));
                            $record->session_notes = ($existing !== '' ? $existing."\n\n" : '').'[Prochain RDV] '.$note;
                            $record->save();
                        }

                        app(PastoralSessionService::class)->scheduleNext($record, $data['next_appointment_at']);

                        Notification::make()
                            ->title('Prochain RDV planifié')
                            ->body('Le dossier reste ouvert (couleur suivi).')
                            ->warning()
                            ->send();
                    }),
                Action::make('reopenDossier')
                    ->label('Réouvrir')
                    ->icon('heroicon-o-lock-open')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (SiteInquiry $record): bool => PastoralAccess::isDossierClosed($record)
                        && PastoralAccess::canReopen(auth()->user() instanceof User ? auth()->user() : null))
                    ->action(function (SiteInquiry $record): void {
                        $user = auth()->user();
                        if (! $user instanceof User) {
                            return;
                        }
                        app(PastoralSessionService::class)->reopen($record, $user);
                        Notification::make()->title('Dossier réouvert')->success()->send();
                    }),
                Action::make('adminRedirect')
                    ->label('Rediriger (admin)')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->visible(fn (SiteInquiry $record): bool => $record->received_at === null
                        && ! PastoralAccess::isDossierClosed($record)
                        && PastoralAccess::canAdminRedirect(auth()->user() instanceof User ? auth()->user() : null))
                    ->form(fn (SiteInquiry $record): array => self::transferMinisterForm($record, 'Redirection administrative'))
                    ->action(function (SiteInquiry $record, array $data): void {
                        self::applyMinisterTransfer($record, $data, 'Redirection admin');
                        Notification::make()
                            ->title('Dossier redirigé')
                            ->body('Le pasteur destinataire a été notifié (mail / SMS).')
                            ->success()
                            ->send();
                    }),
                Action::make('orient')
                    ->label('Orienter')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->visible(fn (SiteInquiry $record): bool => PastoralAccess::canOrient(auth()->user() instanceof User ? auth()->user() : null)
                        && $record->received_at !== null
                        && ! PastoralAccess::isDossierClosed($record))
                    ->form(fn (SiteInquiry $record): array => self::transferMinisterForm($record, 'Note d’orientation'))
                    ->action(function (SiteInquiry $record, array $data): void {
                        $user = auth()->user();
                        $from = PastoralAccess::linkedMinister($user instanceof User ? $user : null);

                        self::applyMinisterTransfer(
                            $record,
                            $data,
                            'Orientation',
                            $from?->id ?? $record->minister_id,
                        );

                        Notification::make()
                            ->title('Fidèle orienté')
                            ->body('Le pasteur destinataire a été notifié (mail / SMS).')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Formulaire commun de transfert / orientation vers un autre pasteur.
     *
     * @return array<int, Select|Textarea>
     */
    private static function transferMinisterForm(SiteInquiry $record, string $noteLabel): array
    {
        return [
            Select::make('minister_id')
                ->label('Vers le pasteur')
                ->options(fn (): array => Minister::query()
                    ->where('is_active', true)
                    ->where('id', '!=', $record->minister_id)
                    ->orderBy('fullname')
                    ->get()
                    ->mapWithKeys(fn (Minister $m): array => [
                        $m->id => MinisterResource::normalizeLegacyValue($m->fullname) ?? (string) $m->id,
                    ])
                    ->all())
                ->required()
                ->searchable(),
            Textarea::make('orientation_note')
                ->label($noteLabel)
                ->rows(3),
        ];
    }

    /**
     * Applique un transfert de pasteur, remet en attente et notifie le destinataire.
     *
     * @param  array{minister_id?: mixed, orientation_note?: mixed}  $data
     */
    private static function applyMinisterTransfer(
        SiteInquiry $record,
        array $data,
        string $notePrefix,
        ?int $fromMinisterId = null,
    ): void {
        $note = trim((string) ($data['orientation_note'] ?? ''));
        $existingNotes = trim((string) ($record->session_notes ?? ''));
        $append = $note !== ''
            ? ($existingNotes !== '' ? $existingNotes."\n\n" : '').'['.$notePrefix.'] '.$note
            : $existingNotes;

        $record->update([
            'oriented_from_minister_id' => $fromMinisterId ?? $record->minister_id,
            'minister_id' => (int) $data['minister_id'],
            'reception_status' => SiteInquiry::RECEPTION_ORIENTED,
            'dossier_status' => SiteInquiry::DOSSIER_OPEN,
            'received_at' => null,
            'session_started_at' => null,
            'session_duration_minutes' => null,
            'completed_at' => null,
            'closed_at' => null,
            'session_notes' => $append !== '' ? $append : null,
        ]);

        app(PastoralTransferNotificationService::class)
            ->notifyDestinationMinister($record->fresh() ?? $record, $notePrefix);
    }

    public static function getWidgets(): array
    {
        return [
            PastoralAppointmentStatsOverviewWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPastoralReceptions::route('/'),
            'history' => Pages\PastoralReceptionHistory::route('/history'),
            'view' => Pages\ViewPastoralReception::route('/{record}'),
            'edit' => Pages\EditPastoralReception::route('/{record}/edit'),
        ];
    }

    public static function getTourStepDescription(): ?string
    {
        return 'Gérez les rendez-vous de réception pastorale.';
    }

    /**
     * @return list<string>
     */
    public static function getTourStepFeatures(): array
    {
        return [
            'Accuser réception et démarrer le chrono',
            'Clôturer, suspendre ou planifier un prochain RDV',
            'Suivre le respect du temps dans l’historique',
        ];
    }

    public static function getTourStepSort(): int
    {
        return 15;
    }
}
