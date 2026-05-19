<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

class EventResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Informations de l evenement')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('designation.fr')->label('Designation (FR)')->required()->columnSpan(8),
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                1 => 'Conference',
                                2 => 'Culte',
                                3 => 'Special',
                            ])
                            ->columnSpan(4),
                        TextInput::make('theme.fr')->label('Theme (FR)')->columnSpan(8),
                        TextInput::make('lieu')->label('Lieu')->columnSpan(4),
                    ]),
                Section::make('Planification')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        DateTimePicker::make('date_debut')->label('Debut')->columnSpan(6),
                        DateTimePicker::make('date_fin')->label('Fin')->columnSpan(6),
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('designation')->label('Designation')->formatStateUsing(fn ($state): string => static::translateState($state))->searchable(),
                TextColumn::make('theme')->label('Theme')->formatStateUsing(fn ($state): string => static::translateState($state))->limit(40),
                TextColumn::make('date_debut')->label('Debut')->dateTime()->sortable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    protected static function translateState(mixed $state): string
    {
        if (is_array($state)) {
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale', 'en');

            return (string) ($state[$locale]
                ?? $state[$fallback]
                ?? collect($state)->first(fn ($item): bool => filled($item))
                ?? '');
        }

        if (! is_string($state)) {
            return '';
        }

        if (blank($state)) {
            return $state;
        }

        $decoded = json_decode($state, true);

        if (! is_array($decoded)) {
            return $state;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($decoded[$locale] ?? $decoded[$fallback] ?? collect($decoded)->first() ?? '');
    }
}
