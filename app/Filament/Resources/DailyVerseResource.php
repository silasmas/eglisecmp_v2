<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DailyVerseResource\Pages;
use App\Models\DailyVerse;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Ressource Filament : versets du jour (visibilite 24 h).
 */
class DailyVerseResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = DailyVerse::class;

    protected static ?string $navigationLabel = 'Lecture du jour';

    protected static ?string $modelLabel = 'Lecture du jour';

    protected static ?string $pluralModelLabel = 'Lectures du jour';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Lecture du jour')
                    ->description('Une fois la date de publication atteinte, le texte reste affiché 24 heures sur le site (bandeau hero et modale « Lecture du jour »).')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        DateTimePicker::make('publish_at')
                            ->label('Publication (début des 24 h)')
                            ->required()
                            ->seconds(false)
                            ->native(false)
                            ->helperText('Moment où la lecture apparaît sur le site. La fin d’affichage est calculée automatiquement (+24 h).')
                            ->columnSpan(6),
                        Placeholder::make('visible_until_preview')
                            ->label('Fin de visibilité (automatique +24 h)')
                            ->content(function (Get $get): string {
                                $raw = $get('publish_at');

                                if (blank($raw)) {
                                    return '—';
                                }

                                try {
                                    return Carbon::parse($raw)->addHours(24)->timezone(config('app.timezone'))->format('d/m/Y H:i');
                                } catch (\Throwable) {
                                    return '—';
                                }
                            })
                            ->columnSpan(6),
                        TextInput::make('reference.fr')
                            ->label('Référence biblique (FR)')
                            ->required()
                            ->helperText('Référence courte affichée sous le titre (ex. Jean 3:16).')
                            ->columnSpan(12),
                        Textarea::make('body.fr')
                            ->label('Texte de la lecture (FR)')
                            ->required()
                            ->rows(8)
                            ->helperText('Corps du passage ou paraphrase ; c’est le contenu principal de la modale.')
                            ->columnSpanFull(),
                        FileUpload::make('image_url.fr')
                            ->label('Vignette (FR)')
                            ->image()
                            ->disk('public')
                            ->directory('daily-verses')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->helperText('Illustration affichée avec la lecture sur le hero et dans la modale.')
                            ->columnSpan(6),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Décochez pour masquer cette entrée sans la supprimer.')
                            ->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Vignette')
                    ->square()
                    ->size(44)
                    ->getStateUsing(fn (?DailyVerse $record): ?string => is_array($record?->image_url)
                        ? ($record->image_url['fr'] ?? $record->image_url[app()->getLocale()] ?? null)
                        : null),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->formatStateUsing(fn ($state, ?DailyVerse $record): string => self::firstLocaleString($record?->reference)),
                TextColumn::make('publish_at')->label('Debut')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('visible_until')->label('Fin')->dateTime('d/m/Y H:i')->sortable(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('publish_at', 'desc')
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
            'index' => Pages\ListDailyVerses::route('/'),
            'create' => Pages\CreateDailyVerse::route('/create'),
            'edit' => Pages\EditDailyVerse::route('/{record}/edit'),
        ];
    }

    /**
     * @param  mixed  $value  Tableau multilingue ou null.
     */
    protected static function firstLocaleString(mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($value[$locale]
            ?? $value[$fallback]
            ?? collect($value)->first(fn ($item): bool => filled($item))
            ?? '');
    }
}
