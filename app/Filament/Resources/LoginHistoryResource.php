<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LoginHistoryResource\Pages;
use App\Models\LoginHistory;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Historique des connexions au tableau de bord.
 */
class LoginHistoryResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = LoginHistory::class;

    protected static ?string $navigationLabel = 'Historique connexions';

    protected static ?string $modelLabel = 'Connexion';

    protected static ?string $pluralModelLabel = 'Historique des connexions';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 90;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Connexion')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')->label('Nom')->columnSpan(4),
                        TextEntry::make('email')->label('E-mail')->columnSpan(4),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->formatStateUsing(fn (string $state): string => $state === LoginHistory::STATUS_SUCCESS ? 'Réussie' : 'Échouée')
                            ->badge()
                            ->color(fn (string $state): string => $state === LoginHistory::STATUS_SUCCESS ? 'success' : 'danger')
                            ->columnSpan(4),
                        TextEntry::make('ip_address')->label('IP')->columnSpan(4),
                        TextEntry::make('guard')->label('Guard')->columnSpan(4),
                        TextEntry::make('logged_in_at')->label('Date')->dateTime('d/m/Y H:i:s')->columnSpan(4),
                        TextEntry::make('user_agent')->label('Navigateur')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('logged_in_at', 'desc')
            ->columns([
                TextColumn::make('logged_in_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('name')->label('Nom')->searchable()->placeholder('—'),
                TextColumn::make('email')->label('E-mail')->searchable()->placeholder('—'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === LoginHistory::STATUS_SUCCESS ? 'Réussie' : 'Échouée')
                    ->color(fn (string $state): string => $state === LoginHistory::STATUS_SUCCESS ? 'success' : 'danger'),
                TextColumn::make('ip_address')->label('IP')->toggleable(),
                TextColumn::make('guard')->label('Guard')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        LoginHistory::STATUS_SUCCESS => 'Réussie',
                        LoginHistory::STATUS_FAILED => 'Échouée',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoginHistories::route('/'),
            'view' => Pages\ViewLoginHistory::route('/{record}'),
        ];
    }
}
