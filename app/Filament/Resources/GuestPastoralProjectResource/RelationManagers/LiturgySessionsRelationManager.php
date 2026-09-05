<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\RelationManagers;

use App\Models\GuestEventOutfit;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Liturgie des cultes (sessions + items horaires).
 */
class LiturgySessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'liturgySessions';

    protected static ?string $title = 'Liturgie des cultes';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Select::make('session_key')
                    ->label('Session')
                    ->options(GuestEventOutfit::sessionOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if (filled($state) && is_string($state)) {
                            $set('title', GuestEventOutfit::sessionOptions()[$state] ?? $state);
                        }
                    })
                    ->columnSpan(4),
                TextInput::make('title')->label('Titre affiché')->required()->columnSpan(4),
                TimePicker::make('starts_at_time')->label('Début')->seconds(false)->columnSpan(2),
                TimePicker::make('ends_at_time')->label('Fin')->seconds(false)->columnSpan(2),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->columnSpan(3),
                Repeater::make('items')
                    ->relationship()
                    ->label('Déroulement')
                    ->columnSpanFull()
                    ->orderColumn('sort_order')
                    ->schema([
                        TimePicker::make('starts_at_time')->label('De')->seconds(false)->columnSpan(2),
                        TimePicker::make('ends_at_time')->label('À')->seconds(false)->columnSpan(2),
                        TextInput::make('duration_minutes')->label('Min')->numeric()->columnSpan(2),
                        TextInput::make('label')->label('Activité')->required()->columnSpan(6),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->addActionLabel('Ajouter une ligne'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('session_key')
                    ->label('Clé')
                    ->formatStateUsing(fn (?string $state): string => GuestEventOutfit::sessionOptions()[$state ?? ''] ?? ($state ?? '—')),
                TextColumn::make('title')->label('Titre'),
                TextColumn::make('starts_at_time')->label('Début')->placeholder('—'),
                TextColumn::make('ends_at_time')->label('Fin')->placeholder('—'),
                TextColumn::make('items_count')->counts('items')->label('Lignes'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une session'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
