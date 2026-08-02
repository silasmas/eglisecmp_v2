<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\ProvidesAdminTourStep;
use App\Filament\Resources\PastoralReceptionResource\Pages;
use App\Filament\Widgets\PastoralAppointmentStatsOverviewWidget;
use App\Models\Minister;
use App\Models\SiteInquiry;
use App\Models\User;
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
 * Module pasteurs : réception des fidèles, notes, conclusion, orientation (titulaire).
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
     * Visible pour les comptes pasteurs liés ou les admins.
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
     * Indique si un utilisateur pasteur lié peut gérer ce dossier.
     */
    public static function canView(Model $record): bool
    {
        return static::canManageRecord($record);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManageRecord($record);
    }

    /**
     * Autorise admin / titulaire / pasteur assigné.
     */
    private static function canManageRecord(Model $record): bool
    {
        if (! $record instanceof SiteInquiry) {
            return false;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('View:SiteInquiry') || $user->hasRole('super_admin') || PastoralAccess::canViewAllAppointments($user)) {
            return true;
        }

        $minister = PastoralAccess::linkedMinister($user);

        return $minister !== null && (int) $record->minister_id === (int) $minister->id;
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
                        DateTimePicker::make('preferred_at')
                            ->label('Créneau')
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
                            ->columnSpan(4),
                        TextEntry::make('reception_status')
                            ->label('Réception')
                            ->formatStateUsing(fn (?string $state): string => SiteInquiry::receptionStatusOptions()[$state] ?? ($state ?? '—'))
                            ->columnSpan(3),
                        TextEntry::make('received_at')
                            ->label('Reçu le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non reçu')
                            ->columnSpan(3),
                        TextEntry::make('appointment_status')
                            ->label('Confirmation')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                SiteInquiry::STATUS_PENDING => 'En attente',
                                SiteInquiry::STATUS_CONFIRMED => 'Confirmé',
                                SiteInquiry::STATUS_DECLINED => 'Refusé',
                                default => $state,
                            })
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
                    ->toggleable(),
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
                TextColumn::make('received_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Non reçu')
                    ->toggleable(),
                TextColumn::make('appointment_status')
                    ->label('Confirm.')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SiteInquiry::STATUS_PENDING => 'Attente',
                        SiteInquiry::STATUS_CONFIRMED => 'OK',
                        SiteInquiry::STATUS_DECLINED => 'Refusé',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('appointment_reason')
                    ->label('Motif')
                    ->options(AppointmentReasons::options()),
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
                Filter::make('this_month')
                    ->label('Ce mois')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereBetween('preferred_at', [now()->startOfMonth(), now()->endOfMonth()])),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->label('Dossier')
                    ->mutateFormDataUsing(function (array $data, SiteInquiry $record): array {
                        if (($data['reception_status'] ?? null) === SiteInquiry::RECEPTION_IN_PROGRESS
                            && $record->received_at === null) {
                            $data['received_at'] = now();
                        }
                        if (($data['reception_status'] ?? null) === SiteInquiry::RECEPTION_COMPLETED) {
                            $data['completed_at'] = now();
                        }

                        return $data;
                    }),
                Action::make('markReceived')
                    ->label('Marquer reçu')
                    ->icon('heroicon-o-hand-raised')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Accuser réception du fidèle')
                    ->modalDescription('Confirmez que vous avez reçu ce fidèle. Vous pourrez ensuite saisir les notes de réception.')
                    ->visible(fn (SiteInquiry $record): bool => $record->received_at === null
                        && $record->reception_status !== SiteInquiry::RECEPTION_COMPLETED
                        && PastoralAccess::canMarkReceived(
                            auth()->user() instanceof User ? auth()->user() : null,
                            (int) ($record->minister_id ?? 0),
                        ))
                    ->action(function (SiteInquiry $record): void {
                        $record->update([
                            'received_at' => now(),
                            'reception_status' => SiteInquiry::RECEPTION_IN_PROGRESS,
                        ]);

                        Notification::make()
                            ->title('Réception accusée')
                            ->body('Le dossier est maintenant en cours.')
                            ->success()
                            ->send();
                    }),
                Action::make('adminRedirect')
                    ->label('Rediriger (admin)')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->visible(fn (SiteInquiry $record): bool => $record->received_at === null
                        && $record->reception_status !== SiteInquiry::RECEPTION_COMPLETED
                        && PastoralAccess::canAdminRedirect(auth()->user() instanceof User ? auth()->user() : null))
                    ->form(fn (SiteInquiry $record): array => self::transferMinisterForm($record, 'Redirection administrative'))
                    ->action(function (SiteInquiry $record, array $data): void {
                        self::applyMinisterTransfer($record, $data, 'Redirection admin');

                        Notification::make()
                            ->title('Dossier redirigé')
                            ->body('Le pasteur destinataire devra accuser réception.')
                            ->success()
                            ->send();
                    }),
                Action::make('orient')
                    ->label('Orienter')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->visible(fn (SiteInquiry $record): bool => PastoralAccess::canOrient(auth()->user() instanceof User ? auth()->user() : null)
                        && $record->received_at !== null
                        && $record->reception_status !== SiteInquiry::RECEPTION_COMPLETED)
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
                            ->title('Fidèle orienté vers un autre pasteur')
                            ->body('Le pasteur destinataire devra accuser réception.')
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
     * Applique un transfert de pasteur et remet le dossier en attente de réception.
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
            'received_at' => null,
            'completed_at' => null,
            'session_notes' => $append !== '' ? $append : null,
        ]);
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
            'Voir les demandes du jour',
            'Confirmer ou refuser un RDV',
            'Suivre vos créneaux',
        ];
    }

    public static function getTourStepSort(): int
    {
        return 15;
    }
}
