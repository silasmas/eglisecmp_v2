<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MinisterReceptionScheduleResource\Pages;
use App\Models\Minister;
use App\Models\MinisterReceptionSchedule;
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
use Filament\Tables\Table;
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
                ->required(),
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
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
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
