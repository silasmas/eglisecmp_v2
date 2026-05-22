<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MinisterReceptionScheduleResource\Pages;
use App\Models\Minister;
use App\Models\MinisterReceptionSchedule;
use App\Support\MinisterReceptionScheduleGuard;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Gestion des horaires de réception des pasteurs (rendez-vous publics).
 */
class MinisterReceptionScheduleResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = MinisterReceptionSchedule::class;

    protected static ?string $navigationLabel = 'Horaires pasteurs';

    protected static ?string $modelLabel = 'Horaire de réception';

    protected static ?string $pluralModelLabel = 'Horaires de réception';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('minister_id')
                ->label('Pasteur')
                ->relationship('minister', 'fullname')
                ->getOptionLabelFromRecordUsing(fn (Minister $record): string => MinisterResource::normalizeLegacyValue($record->fullname) ?? (string) $record->id)
                ->searchable()
                ->preload()
                ->required(),
            Select::make('bureau_id')
                ->label('Bureau')
                ->relationship('bureau', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Deux pasteurs peuvent recevoir le même créneau dans des bureaux différents.'),
            Select::make('day_of_week')
                ->label('Jour')
                ->options([
                    1 => 'Lundi',
                    2 => 'Mardi',
                    3 => 'Mercredi',
                    4 => 'Jeudi',
                    5 => 'Vendredi',
                    6 => 'Samedi',
                    7 => 'Dimanche',
                ])
                ->required(),
            TimePicker::make('starts_at')
                ->label('Début')
                ->seconds(false)
                ->required(),
            TimePicker::make('ends_at')
                ->label('Fin')
                ->seconds(false)
                ->required(),
            TextInput::make('slot_minutes')
                ->label('Durée créneau (minutes)')
                ->numeric()
                ->default(30)
                ->minValue(15)
                ->maxValue(120)
                ->required(),
            Toggle::make('is_active')
                ->label('Actif')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('minister.fullname')
                    ->label('Pasteur')
                    ->formatStateUsing(fn ($state): string => MinisterResource::normalizeLegacyValue($state) ?? '—')
                    ->searchable(),
                TextColumn::make('bureau.name')
                    ->label('Bureau')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('bureau_id')
                    ->label('RDV en ligne')
                    ->badge()
                    ->formatStateUsing(fn (?int $state, MinisterReceptionSchedule $record): string => $record->isPubliclyBookable()
                        ? 'Proposé aux fidèles'
                        : 'Masqué')
                    ->color(fn (?int $state, MinisterReceptionSchedule $record): string => $record->isPubliclyBookable()
                        ? 'success'
                        : 'warning')
                    ->tooltip(fn (MinisterReceptionSchedule $record): ?string => $record->isPubliclyBookable()
                        ? null
                        : 'Affectez un bureau à ce créneau pour que le pasteur apparaisse sur la prise de RDV en ligne.'),
                TextColumn::make('day_of_week')
                    ->label('Jour')
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'Lundi',
                        2 => 'Mardi',
                        3 => 'Mercredi',
                        4 => 'Jeudi',
                        5 => 'Vendredi',
                        6 => 'Samedi',
                        7 => 'Dimanche',
                        default => (string) $state,
                    }),
                TextColumn::make('starts_at')->label('Début')->time('H:i'),
                TextColumn::make('ends_at')->label('Fin')->time('H:i'),
                TextColumn::make('slot_minutes')->label('Créneau (min)'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('minister_id')
            ->filters([
                TernaryFilter::make('has_bureau')
                    ->label('Bureau renseigné')
                    ->placeholder('Tous')
                    ->trueLabel('Avec bureau')
                    ->falseLabel('Sans bureau')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('bureau_id'),
                        false: fn ($query) => $query->whereNull('bureau_id'),
                    ),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * Vérifie qu’aucune autre plage n’occupe le même bureau sur le même créneau.
     *
     * @param  array<string, mixed>  $data  Données du formulaire.
     * @param  int|null  $exceptId  Identifiant de la plage en édition.
     */
    public static function assertBureauSlotAvailable(array $data, ?int $exceptId = null): void
    {
        $bureauId = (int) ($data['bureau_id'] ?? 0);
        $dayOfWeek = (int) ($data['day_of_week'] ?? 0);
        $startsAt = (string) ($data['starts_at'] ?? '');
        $endsAt = (string) ($data['ends_at'] ?? '');

        if ($bureauId <= 0 || $dayOfWeek <= 0 || $startsAt === '' || $endsAt === '') {
            return;
        }

        $conflict = MinisterReceptionScheduleGuard::findConflictingSchedule(
            $bureauId,
            $dayOfWeek,
            $startsAt,
            $endsAt,
            $exceptId,
        );

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'starts_at' => MinisterReceptionScheduleGuard::conflictMessage($conflict),
            ]);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMinisterReceptionSchedules::route('/'),
            'create' => Pages\CreateMinisterReceptionSchedule::route('/create'),
            'edit' => Pages\EditMinisterReceptionSchedule::route('/{record}/edit'),
        ];
    }
}
