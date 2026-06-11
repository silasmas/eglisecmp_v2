<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\YoutubeSyncRunResource\Pages;
use App\Models\YoutubeSyncRun;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Historique et suivi des synchronisations YouTube (cron, manuel, file).
 */
class YoutubeSyncRunResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = YoutubeSyncRun::class;

    protected static ?string $navigationLabel = 'Synchronisations YouTube';

    protected static ?string $modelLabel = 'Synchronisation YouTube';

    protected static ?string $pluralModelLabel = 'Synchronisations YouTube';

    protected static ?string $slug = 'youtube-sync-runs';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?int $navigationSort = 85;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Résumé')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Statut')
                            ->formatStateUsing(fn (YoutubeSyncRun $record): string => $record->statusLabel())
                            ->badge()
                            ->color(fn (YoutubeSyncRun $record): string => match ($record->status) {
                                YoutubeSyncRun::STATUS_SUCCESS => 'success',
                                YoutubeSyncRun::STATUS_FAILED => 'danger',
                                YoutubeSyncRun::STATUS_RUNNING => 'warning',
                                default => 'gray',
                            })
                            ->columnSpan(3),
                        TextEntry::make('source')
                            ->label('Origine')
                            ->formatStateUsing(fn (YoutubeSyncRun $record): string => $record->sourceLabel())
                            ->columnSpan(3),
                        TextEntry::make('triggeredBy.name')
                            ->label('Déclenchée par')
                            ->placeholder('—')
                            ->columnSpan(3),
                        TextEntry::make('duration_seconds')
                            ->label('Durée')
                            ->formatStateUsing(fn (?int $state): string => $state !== null ? $state.' s' : '—')
                            ->columnSpan(3),
                        TextEntry::make('started_at')
                            ->label('Début')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—')
                            ->columnSpan(4),
                        TextEntry::make('finished_at')
                            ->label('Fin')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('—')
                            ->columnSpan(4),
                        TextEntry::make('is_full_sync')
                            ->label('Mode')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Complète' : 'Incrémentale')
                            ->columnSpan(2),
                        TextEntry::make('is_dry_run')
                            ->label('Simulation')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                            ->columnSpan(2),
                    ]),
                Section::make('Résultat')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('message')
                            ->label('Message')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('error_message')
                            ->label('Erreur')
                            ->placeholder('—')
                            ->visible(fn (YoutubeSyncRun $record): bool => filled($record->error_message))
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),
                Section::make('Statistiques')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('playlists')->label('Playlists')->columnSpan(2),
                        TextEntry::make('videos')->label('Vidéos lues')->columnSpan(2),
                        TextEntry::make('created_count')->label('Créées')->columnSpan(2),
                        TextEntry::make('updated_count')->label('Mises à jour')->columnSpan(2),
                        TextEntry::make('unchanged_count')->label('Déjà à jour')->columnSpan(2),
                        TextEntry::make('skipped_count')->label('Ignorées')->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->poll('15s')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (YoutubeSyncRun $record): string => $record->statusLabel())
                    ->color(fn (YoutubeSyncRun $record): string => match ($record->status) {
                        YoutubeSyncRun::STATUS_SUCCESS => 'success',
                        YoutubeSyncRun::STATUS_FAILED => 'danger',
                        YoutubeSyncRun::STATUS_RUNNING => 'warning',
                        YoutubeSyncRun::STATUS_QUEUED => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Origine')
                    ->formatStateUsing(fn (YoutubeSyncRun $record): string => $record->sourceLabel())
                    ->toggleable(),
                TextColumn::make('message')
                    ->label('Résumé')
                    ->limit(60)
                    ->placeholder('—')
                    ->tooltip(fn (YoutubeSyncRun $record): ?string => $record->message),
                TextColumn::make('error_message')
                    ->label('Erreur')
                    ->limit(50)
                    ->color('danger')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_count')
                    ->label('Créées')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('updated_count')
                    ->label('MAJ')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('duration_seconds')
                    ->label('Durée (s)')
                    ->placeholder('—')
                    ->alignCenter(),
                TextColumn::make('started_at')
                    ->label('Début')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        YoutubeSyncRun::STATUS_SUCCESS => 'Réussie',
                        YoutubeSyncRun::STATUS_FAILED => 'Échouée',
                        YoutubeSyncRun::STATUS_RUNNING => 'En cours',
                        YoutubeSyncRun::STATUS_QUEUED => 'En file',
                    ]),
                SelectFilter::make('source')
                    ->label('Origine')
                    ->options([
                        'scheduler' => 'Cron',
                        'scheduler_manual' => 'Admin — test scheduler',
                        'filament' => 'Admin — page synchro',
                        'posts_page' => 'Admin — publications',
                        'queue' => 'File d’attente',
                        'command' => 'Ligne de commande',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYoutubeSyncRuns::route('/'),
            'view' => Pages\ViewYoutubeSyncRun::route('/{record}'),
        ];
    }
}
