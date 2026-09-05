<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestPastoralProjectResource\RelationManagers;

use App\Models\GuestEventOutfit;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Tenues prévues par session (portail invité).
 */
class OutfitsRelationManager extends RelationManager
{
    protected static string $relationship = 'outfits';

    protected static ?string $title = 'Tenues de l’événement';

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
                    ->columnSpan(4),
                TextInput::make('title')->label('Titre')->required()->columnSpan(5),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0)->columnSpan(3),
                Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Photo de la tenue')
                    ->image()
                    ->disk('public')
                    ->directory('guest-outfits')
                    ->visibility('public')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image_path')->label('Photo')->disk('public')->circular(),
                TextColumn::make('session_key')
                    ->label('Session')
                    ->formatStateUsing(fn (?string $state): string => GuestEventOutfit::sessionOptions()[$state ?? ''] ?? ($state ?? '—')),
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('sort_order')->label('Ordre'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une tenue'),
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
