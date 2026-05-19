<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OffrandeResource\Pages;
use App\Models\Offrande;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

class OffrandeResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Offrande::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Paiements';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('nom')->label('Nom')->required()->maxLength(255),
            RichEditor::make('description')->label('Description')->columnSpanFull(),
            Toggle::make('is_active')->label('Actif')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => static::getTranslatedState($state)),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('created_at')->label('Cree le')->dateTime()->sortable()->toggleable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    protected static function getTranslatedState(mixed $state): string
    {
        if (! is_string($state) || blank($state)) {
            return (string) $state;
        }

        $decoded = json_decode($state, true);

        if (! is_array($decoded)) {
            return $state;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($decoded[$locale]
            ?? $decoded[$fallback]
            ?? collect($decoded)->first(fn ($item): bool => filled($item))
            ?? '');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffrandes::route('/'),
            'create' => Pages\CreateOffrande::route('/create'),
            'edit' => Pages\EditOffrande::route('/{record}/edit'),
        ];
    }
}
