<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\ProvidesAdminTourStep;
use App\Filament\Resources\ChurchDepartmentResource\Pages;
use App\Models\ChurchDepartment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

/**
 * Gestion des départements ministériels (ouvriers).
 */
class ChurchDepartmentResource extends Resource
{
    use HasTabbedActions;
    use ProvidesAdminTourStep;

    protected static ?string $model = ChurchDepartment::class;

    protected static ?string $navigationLabel = 'Départements';

    protected static ?string $modelLabel = 'Département';

    protected static ?string $pluralModelLabel = 'Départements';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Ouvriers';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Département')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nom')->required()->columnSpan(6),
                        TextInput::make('slug')->label('Slug')->helperText('Laissé vide = généré automatiquement')->columnSpan(3),
                        TextInput::make('color')->label('Couleur badge')->type('color')->default('#7b1d3e')->columnSpan(3),
                        Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),
                        Select::make('manager_user_id')
                            ->label('Responsable')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Peut valider les inscriptions de ce département.')
                            ->columnSpan(6),
                        TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->columnSpan(3),
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('color')
                    ->label('Couleur')
                    ->formatStateUsing(fn (string $state): string => $state)
                    ->color(fn (string $state): string => 'gray'),
                TextColumn::make('manager.name')->label('Responsable')->placeholder('—'),
                TextColumn::make('workers_count')->counts('workers')->label('Ouvriers'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading('Supprimer le département')
                    ->modalDescription('Les ouvriers rattachés à ce département seront également supprimés. Cette action est irréversible.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection')
                        ->modalHeading('Supprimer les départements sélectionnés')
                        ->modalDescription('Les ouvriers rattachés à ces départements seront également supprimés. Cette action est irréversible.')
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChurchDepartments::route('/'),
            'create' => Pages\CreateChurchDepartment::route('/create'),
            'edit' => Pages\EditChurchDepartment::route('/{record}/edit'),
        ];
    }

    public static function getTourStepDescription(): ?string
    {
        return 'Organisez les départements ministériels et leurs responsables.';
    }

    /**
     * @return list<string>
     */
    public static function getTourStepFeatures(): array
    {
        return [
            'Créer / modifier un département',
            'Assigner un responsable',
            'Définir la couleur badge',
        ];
    }

    public static function getTourStepSort(): int
    {
        return 10;
    }
}
