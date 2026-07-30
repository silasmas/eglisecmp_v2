<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MinisterResource\Pages;
use App\Models\Minister;
use App\Support\FilamentImageUrl;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Pasteur')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('fullname')->label('Nom complet')->required()->columnSpan(6),
                        TextInput::make('image_url')->label('Photo (URL ou chemin)')->columnSpan(6),
                        TextInput::make('contact')->label('Contact')->columnSpan(4),
                        Select::make('user_id')
                            ->label('Compte admin lié')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Permet au pasteur de se connecter et gérer ses rendez-vous.')
                            ->columnSpan(4),
                        Toggle::make('is_titular')
                            ->label('Pasteur titulaire')
                            ->helperText('Seul le titulaire peut orienter un fidèle vers un autre pasteur et voir tous les RDV.')
                            ->columnSpan(2),
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(2),
                    ]),
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
                TextColumn::make('user.name')->label('Compte')->placeholder('—')->toggleable(),
                IconColumn::make('is_titular')->label('Titulaire')->boolean(),
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
