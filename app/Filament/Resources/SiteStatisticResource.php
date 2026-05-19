<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SiteStatisticResource\Pages;
use App\Models\SiteStatistic;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Liste et édition des chiffres affichés sur l’accueil (« En chiffres »).
 */
class SiteStatisticResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = SiteStatistic::class;

    protected static ?string $navigationLabel = 'Statistiques accueil';

    protected static ?string $modelLabel = 'Statistique';

    protected static ?string $pluralModelLabel = 'Statistiques';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Bloc « En chiffres »')
                    ->description('Ici `icon_key` détermine l’icône sur le site pour le libellé public.')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(2),
                        Select::make('icon_key')
                            ->label('Icône (SPA)')
                            ->options([
                                'users' => 'Fidèles (users)',
                                'network' => 'Extensions (network)',
                                'grid' => 'Cellules (grid)',
                                'pastors' => 'Pastoraux (pastors)',
                            ])
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('label')
                            ->label('Libellé affiché')
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('numeric_value')
                            ->numeric()
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('suffix')
                            ->label('Suffixe (facultatif, ex. %)')
                            ->maxLength(16)
                            ->columnSpan(4),
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
                TextColumn::make('icon_key')->label('Icône'),
                TextColumn::make('label')->searchable(),
                TextColumn::make('numeric_value')->numeric()->sortable()->label('Valeur'),
                TextColumn::make('suffix')->label('+'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')->label('Actif'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteStatistics::route('/'),
            'create' => Pages\CreateSiteStatistic::route('/create'),
            'edit' => Pages\EditSiteStatistic::route('/{record}/edit'),
        ];
    }
}
