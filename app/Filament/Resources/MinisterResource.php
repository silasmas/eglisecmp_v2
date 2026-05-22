<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MinisterResource\Pages;
use App\Models\Minister;
use App\Support\FilamentImageUrl;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use TinusG\FilamentHoverImageColumn\HoverImageColumn as ImageColumn;
use UnitEnum;

class MinisterResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Minister::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('fullname')->label('Nom complet')->required(),
            TextInput::make('image_url')->label('Photo'),
            TextInput::make('contact')->label('Contact'),
            Toggle::make('is_active')->label('Actif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Photo')
                    ->circular()
                    ->size(48)
                    ->getStateUsing(fn (Minister $record): ?string => FilamentImageUrl::resolve($record->image_url))
                    ->placeholder('—'),
                TextColumn::make('fullname')
                    ->label('Nom')
                    ->formatStateUsing(fn ($state): string => static::normalizeLegacyValue($state) ?? '')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('contact')->label('Contact'),
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
            'index' => Pages\ListMinisters::route('/'),
            'create' => Pages\CreateMinister::route('/create'),
            'edit' => Pages\EditMinister::route('/{record}/edit'),
        ];
    }

    public static function normalizeLegacyValue(mixed $value): ?string
    {
        if (is_array($value)) {
            return (string) (collect($value)->first(fn ($item): bool => filled($item)) ?? '');
        }

        if (! is_string($value) || blank($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return $value;
        }

        return (string) (collect($decoded)->first(fn ($item): bool => filled($item)) ?? '');
    }
}
