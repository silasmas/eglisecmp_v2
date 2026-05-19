<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SiteInquiryResource\Pages;
use App\Models\SiteInquiry;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Liste des formulaires envoyés depuis les pages prière et rendez-vous.
 */
class SiteInquiryResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = SiteInquiry::class;

    protected static ?string $navigationLabel = 'Demandes (prière / RDV)';

    protected static ?string $modelLabel = 'Demande';

    protected static ?string $pluralModelLabel = 'Demandes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Réception')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('kind')->label('Type')->columnSpan(4),
                        TextEntry::make('name')->label('Nom')->columnSpan(8),
                        TextEntry::make('email')->columnSpan(6),
                        TextEntry::make('phone')->columnSpan(6),
                        TextEntry::make('preferred_at')->dateTime()->label('Date souhaitée')->columnSpan(6),
                        TextEntry::make('created_at')->dateTime()->label('Envoyée le')->columnSpan(6),
                        TextEntry::make('message')
                            ->label('Message')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Aucune édition côté admin : données issues du site uniquement.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kind')->label('Type')->formatStateUsing(
                    fn (string $state): string => match ($state) {
                        SiteInquiry::KIND_PRAYER => 'Prière',
                        SiteInquiry::KIND_APPOINTMENT => 'Rendez-vous',
                        default => $state,
                    }
                ),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->label('Téléphone')->toggleable(),
                TextColumn::make('preferred_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('message')->label('Message')->limit(60)->tooltip(fn (SiteInquiry $r): string => $r->message),
                TextColumn::make('created_at')->label('Réception')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteInquiries::route('/'),
            'view' => Pages\ViewSiteInquiry::route('/{record}'),
        ];
    }
}
