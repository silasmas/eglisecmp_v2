<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ChurchCellResource\Pages;
use App\Models\ChurchCell;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Gestion admin des cellules de maison CMP.
 */
class ChurchCellResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = ChurchCell::class;

    protected static ?string $navigationLabel = 'Cellules';

    protected static ?string $modelLabel = 'Cellule';

    protected static ?string $pluralModelLabel = 'Cellules';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 27;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Cellule')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nom')->required()->columnSpan(6),
                        TextInput::make('slug')->label('Slug')->helperText('Laissé vide = généré automatiquement')->columnSpan(3),
                        TextInput::make('commune')->label('Commune')->required()->columnSpan(3),
                        TextInput::make('day')->label('Jour')->placeholder('Mardi')->columnSpan(3),
                        TextInput::make('time')->label('Heure')->placeholder('18h00')->columnSpan(3),
                        TextInput::make('host')->label('Famille d’accueil')->columnSpan(6),
                        Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                        TextInput::make('address')->label('Adresse')->columnSpan(8),
                        TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->columnSpan(2),
                        Toggle::make('is_active')->label('Active')->default(true)->columnSpan(2),
                    ]),
                Section::make('Localisation (optionnel)')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('lat')->label('Latitude')->numeric()->step('any')->columnSpan(6),
                        TextInput::make('lng')->label('Longitude')->numeric()->step('any')->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('commune')->label('Commune')->searchable(),
                TextColumn::make('day')->label('Jour')->toggleable(),
                TextColumn::make('time')->label('Heure')->toggleable(),
                TextColumn::make('host')->label('Accueil')->toggleable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChurchCells::route('/'),
            'create' => Pages\CreateChurchCell::route('/create'),
            'edit' => Pages\EditChurchCell::route('/{record}/edit'),
        ];
    }
}
