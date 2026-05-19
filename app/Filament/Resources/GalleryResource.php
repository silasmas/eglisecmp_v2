<?php

namespace App\Filament\Resources;

use AhmedAbdelrhman\FilamentMediaGallery\Infolists\Components\MediaGalleryEntry;
use App\Filament\Resources\GalleryResource\Pages;
use App\Models\Gallery;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

class GalleryResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Gallery::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Médias')
                    ->description('Fichiers gérés par la médiathèque Spatie (collection « gallery »).')
                    ->columnSpanFull()
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('gallery_media')
                            ->label('Images et fichiers')
                            ->collection(Gallery::MEDIA_COLLECTION)
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->image()
                            ->columnSpanFull(),
                    ]),
                Section::make('Informations')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('image_url.fr')
                            ->label('Image URL legacy (FR)')
                            ->url()
                            ->columnSpan(6),
                        TextInput::make('description.fr')
                            ->label('Description (FR)')
                            ->columnSpan(6),
                        Select::make('post_id')
                            ->label('Post')
                            ->relationship('post', 'slug')
                            ->searchable()
                            ->preload()
                            ->columnSpan(6),
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(6),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Galerie média')
                    ->columnSpanFull()
                    ->schema([
                        MediaGalleryEntry::make('gallery_view')
                            ->label('Fichiers Spatie')
                            ->collection(Gallery::MEDIA_COLLECTION)
                            ->size(220)
                            ->columnSpanFull(),
                    ]),
                Section::make('Détails')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('description')
                            ->label('Description')
                            ->formatStateUsing(fn ($state): string => static::translateState($state))
                            ->columnSpan(8),
                        IconEntry::make('is_active')->label('Actif')->boolean()->columnSpan(4),
                        TextEntry::make('post.slug')->label('Post')->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('media')
                    ->label('Aperçu')
                    ->collection(Gallery::MEDIA_COLLECTION)
                    ->conversion('thumbnail')
                    ->limit(1)
                    ->square()
                    ->defaultImageUrl(function (Model $record): ?string {
                        if (! $record instanceof Gallery) {
                            return null;
                        }

                        $legacy = static::translateState($record->image_url);

                        return filled($legacy) ? $legacy : null;
                    }),
                TextColumn::make('description')
                    ->label('Description')
                    ->formatStateUsing(fn ($state): string => static::translateState($state))
                    ->limit(50),
                TextColumn::make('post.slug')->label('Post'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'view' => Pages\ViewGallery::route('/{record}'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }

    protected static function translateState(mixed $state): string
    {
        if (is_array($state)) {
            $locale = app()->getLocale();
            $fallback = config('app.fallback_locale', 'en');

            return (string) ($state[$locale] ?? $state[$fallback] ?? collect($state)->first() ?? '');
        }

        if (! is_string($state) || blank($state)) {
            return (string) ($state ?? '');
        }

        $decoded = json_decode($state, true);

        if (! is_array($decoded)) {
            return $state;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($decoded[$locale] ?? $decoded[$fallback] ?? collect($decoded)->first() ?? '');
    }
}
