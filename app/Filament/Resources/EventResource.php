<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use App\Support\EventFeaturedGuard;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Ressource Filament : événements publics du site.
 */
class EventResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Informations de l evenement')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('designation.fr')->label('Designation (FR)')->required()->columnSpan(8),
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                1 => 'Conference',
                                2 => 'Culte',
                                3 => 'Special',
                            ])
                            ->columnSpan(4),
                        TextInput::make('theme.fr')->label('Theme (FR)')->columnSpan(8),
                        TextInput::make('lieu')->label('Lieu')->columnSpan(4),
                        TextInput::make('orateur')->label('Orateur')->columnSpan(6),
                        FileUpload::make('image_url.fr')
                            ->label('Affiche')
                            ->image()
                            ->directory('events')
                            ->columnSpan(6),
                        Textarea::make('description.fr')
                            ->label('Description')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
                Section::make('Planification')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        DateTimePicker::make('date_debut')->label('Debut')->columnSpan(6),
                        DateTimePicker::make('date_fin')->label('Fin')->columnSpan(6),
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(4),
                    ]),
                Section::make('Mise en avant site')
                    ->description('Un seul evenement peut etre en avant. Modale a l entree du site et bouton flottant clignotant.')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        Toggle::make('est_a_la_une')->label('Mettre en avant')->columnSpan(4),
                        DateTimePicker::make('featured_from')->label('Debut mise en avant')->seconds(false)->columnSpan(4),
                        DateTimePicker::make('featured_until')->label('Fin mise en avant')->seconds(false)->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Affiche')
                    ->square()
                    ->size(56)
                    ->getStateUsing(fn (?Event $record): ?string => static::resolveEventPosterUrl($record))
                    ->placeholder('—'),
                TextColumn::make('designation')->label('Designation')->formatStateUsing(fn ($state): string => static::translateState($state))->searchable(),
                TextColumn::make('theme')->label('Theme')->formatStateUsing(fn ($state): string => static::translateState($state))->limit(40),
                TextColumn::make('date_debut')->label('Debut')->dateTime()->sortable(),
                TextColumn::make('featured_from')->label('Avant du')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('featured_until')->label('Avant au')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('est_a_la_une')
                    ->label('En avant')
                    ->updateStateUsing(function (Event $record, bool $state): bool {
                        if ($state) {
                            $record->est_a_la_une = true;
                            EventFeaturedGuard::assertFeaturedWindowValid($record);
                            EventFeaturedGuard::ensureSingleFeaturedExcept($record);
                            $record->save();

                            return true;
                        }

                        $record->update(['est_a_la_une' => false]);

                        return false;
                    }),
                ToggleColumn::make('is_active')->label('Actif'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    protected static function translateState(mixed $state): string
    {
        if (is_array($state)) {
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale', 'en');

            return (string) ($state[$locale]
                ?? $state[$fallback]
                ?? collect($state)->first(fn ($item): bool => filled($item))
                ?? '');
        }

        if (! is_string($state)) {
            return '';
        }

        if (blank($state)) {
            return $state;
        }

        $decoded = json_decode($state, true);

        if (! is_array($decoded)) {
            return $state;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($decoded[$locale] ?? $decoded[$fallback] ?? collect($decoded)->first() ?? '');
    }

    /**
     * URL de l'affiche événement pour la colonne tableau Filament.
     */
    protected static function resolveEventPosterUrl(?Event $record): ?string
    {
        if ($record === null) {
            return null;
        }

        $imageUrl = $record->image_url;

        if (! is_array($imageUrl)) {
            return null;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');
        $path = (string) ($imageUrl[$locale] ?? $imageUrl[$fallback] ?? collect($imageUrl)->first(fn ($item): bool => filled($item)) ?? '');

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
