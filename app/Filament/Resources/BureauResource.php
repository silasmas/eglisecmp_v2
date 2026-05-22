<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BureauResource\Pages;
use App\Models\Bureau;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Gestion des bureaux de réception (rendez-vous pastoraux).
 */
class BureauResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Bureau::class;

    protected static ?string $navigationLabel = 'Bureaux';

    protected static ?string $modelLabel = 'Bureau';

    protected static ?string $pluralModelLabel = 'Bureaux';

    protected static ?int $navigationSort = 34;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nom du bureau')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Ex. Bureau principal, Accueil pasteur, Salle de conseil.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reception_schedules_count')
                    ->label('Horaires pasteurs')
                    ->counts('receptionSchedules')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Bureau $record): bool => static::canDeleteRecord($record))
                    ->before(function (DeleteAction $action, Bureau $record): void {
                        if (! static::canDeleteRecord($record)) {
                            Notification::make()
                                ->title('Suppression impossible')
                                ->body('Ce bureau est utilisé par des horaires pasteurs ou des rendez-vous.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ]);
    }

    /**
     * Indique si le bureau peut être supprimé sans casser les horaires ou rendez-vous.
     *
     * @param  Bureau  $record  Bureau candidat à la suppression.
     */
    public static function canDeleteRecord(Bureau $record): bool
    {
        return ! $record->receptionSchedules()->exists()
            && ! $record->siteInquiries()->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBureaus::route('/'),
            'create' => Pages\CreateBureau::route('/create'),
            'edit' => Pages\EditBureau::route('/{record}/edit'),
        ];
    }
}
