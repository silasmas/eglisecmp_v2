<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ChurchExtensionResource\Pages;
use App\Models\ChurchExtension;
use App\Support\FilamentImageUrl;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
use TinusG\FilamentHoverImageColumn\HoverImageColumn as ImageColumn;
use UnitEnum;

/**
 * Gestion admin des extensions CMP (carte mondiale + dirigeant).
 */
class ChurchExtensionResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = ChurchExtension::class;

    protected static ?string $navigationLabel = 'Extensions';

    protected static ?string $modelLabel = 'Extension';

    protected static ?string $pluralModelLabel = 'Extensions';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 28;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Extension')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nom')->required()->columnSpan(6),
                        TextInput::make('city')->label('Ville')->required()->columnSpan(3),
                        TextInput::make('country')->label('Pays')->required()->columnSpan(3),
                        TextInput::make('address')->label('Adresse')->columnSpan(8),
                        TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->columnSpan(2),
                        Toggle::make('is_active')->label('Active')->default(true)->columnSpan(2),
                        Textarea::make('description')->label('Description')->rows(3)->columnSpanFull(),
                    ]),
                Section::make('Localisation MAP')
                    ->description('Coordonnées GPS pour positionner l’extension sur la carte mondiale du site.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('lat')
                            ->label('Latitude')
                            ->required()
                            ->numeric()
                            ->step('any')
                            ->columnSpan(6),
                        TextInput::make('lng')
                            ->label('Longitude')
                            ->required()
                            ->numeric()
                            ->step('any')
                            ->columnSpan(6),
                    ]),
                Section::make('Dirigeant pastoral')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('leader_name')
                            ->label('Pasteur / couple pastoral')
                            ->columnSpan(6),
                        FileUpload::make('leader_photo_path')
                            ->label('Photo de profil')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('extensions/leaders')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('leader_photo_path')
                    ->label('Photo')
                    ->getStateUsing(fn (ChurchExtension $record): ?string => FilamentImageUrl::resolve($record->leader_photo_path)),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('city')->label('Ville')->searchable(),
                TextColumn::make('country')->label('Pays')->searchable(),
                TextColumn::make('leader_name')->label('Dirigeant')->toggleable(),
                TextColumn::make('lat')->label('Lat')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lng')->label('Lng')->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListChurchExtensions::route('/'),
            'create' => Pages\CreateChurchExtension::route('/create'),
            'edit' => Pages\EditChurchExtension::route('/{record}/edit'),
        ];
    }
}
