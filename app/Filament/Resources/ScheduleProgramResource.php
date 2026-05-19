<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleProgramResource\Pages;
use App\Models\Event;
use App\Models\ScheduleProgram;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Ressource Filament : programmes du site (cultes, hebdo, séminaires, lives).
 */
class ScheduleProgramResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = ScheduleProgram::class;

    protected static ?string $navigationLabel = 'Programmes (site)';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-radio';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Definition')
                    ->description('Type de programme et textes affichés sur la page « Nos rendez-vous » et, pour le live, sur le bandeau du hero.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('kind')
                            ->label('Type')
                            ->required()
                            ->helperText('Définit le rôle de la carte : culte du jour, rendez-vous hebdo, séminaire lié à un événement, créneau de live récurrent ou exception.')
                            ->options([
                                ScheduleProgram::KIND_DAILY => 'Culte du jour',
                                ScheduleProgram::KIND_WEEKLY => 'Hebdomadaire',
                                ScheduleProgram::KIND_SEMINAR => 'Séminaire / conférence à venir',
                                ScheduleProgram::KIND_LIVE => 'Prochain live (créneau récurrent)',
                                ScheduleProgram::KIND_SPECIAL => 'Spécial',
                            ])
                            ->columnSpan(4),
                        TextInput::make('title.fr')
                            ->label('Titre (FR)')
                            ->required()
                            ->helperText('Titre principal affiché sur la carte programme. Soyez clair pour que le visiteur comprenne le rendez-vous en un coup d’œil.')
                            ->columnSpan(8),
                        Textarea::make('description.fr')
                            ->label('Description (FR)')
                            ->rows(4)
                            ->helperText('Court paragraphe : objectif du temps, public concerné, ambiance. Utilisé sur la carte et dans la modale du hero si ce programme correspond au créneau affiché.')
                            ->columnSpanFull(),
                        FileUpload::make('image_url.fr')
                            ->label('Vignette (FR)')
                            ->image()
                            ->disk('public')
                            ->directory('schedule-programs')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->helperText('Image d’accroche sur la carte « Nos rendez-vous ». JPG ou WebP recommandé (max. 5 Mo).')
                            ->columnSpan(6),
                        FileUpload::make('banner_image.fr')
                            ->label('Bannière modale (FR)')
                            ->image()
                            ->disk('public')
                            ->directory('schedule-programs')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->helperText('Grande image en tête de la modale hero. Si vide, la vignette est réutilisée (max. 5 Mo).')
                            ->columnSpan(6),
                        TextInput::make('day_label')
                            ->label('Libellé jour (badge)')
                            ->maxLength(120)
                            ->helperText('Court libellé affiché en badge (ex. « Mercredi », « Dimanche »). Renforce la lisibilité du calendrier.')
                            ->columnSpan(4),
                        Select::make('weekday')
                            ->label('Jour (0 = dimanche)')
                            ->helperText('Pour les types « hebdomadaire » et « live » : jour de la semaine utilisé pour calculer la prochaine occurrence.')
                            ->options([
                                0 => 'Dimanche',
                                1 => 'Lundi',
                                2 => 'Mardi',
                                3 => 'Mercredi',
                                4 => 'Jeudi',
                                5 => 'Vendredi',
                                6 => 'Samedi',
                            ])
                            ->columnSpan(4),
                        TextInput::make('time_label')
                            ->label('Horaire affiché (ex. 17h30 - 19h00)')
                            ->maxLength(120)
                            ->helperText('Plage horaire lisible pour les visiteurs. Indépendante des champs heure/minute du live (affichage marketing).')
                            ->columnSpan(4),
                        TextInput::make('live_hour')
                            ->label('Heure live (0-23)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(23)
                            ->helperText('Heure de début du créneau « live » pour le calcul du décompte sur le hero (avec le jour ci-dessus).')
                            ->columnSpan(2),
                        TextInput::make('live_minute')
                            ->label('Minute live (0-59)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(59)
                            ->default(0)
                            ->helperText('Minute de début ; combinez avec l’heure pour le prochain rendez-vous en ligne.')
                            ->columnSpan(2),
                        TextInput::make('link_url')
                            ->label('Lien (YouTube, inscription…)')
                            ->url()
                            ->maxLength(500)
                            ->helperText('Lien externe : chaîne YouTube, formulaire d’inscription ou page d’information.')
                            ->columnSpan(8),
                        Select::make('event_id')
                            ->label('Événement lié (séminaire)')
                            ->relationship('event', 'designation')
                            ->getOptionLabelFromRecordUsing(fn (Event $record): string => self::translateJson($record->designation) ?: "Événement #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->helperText('Pour un séminaire : rattachez l’événement pour réutiliser son titre et rester cohérent avec l’agenda officiel.')
                            ->columnSpan(4),
                        Select::make('icon_key')
                            ->label('Icône')
                            ->options((array) config('site_public.program_icons', []))
                            ->default('book-open')
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->helperText('Icône affichée sur la carte programme du site (liste alignée sur lucide-react).')
                            ->columnSpan(4),
                        Toggle::make('grid_wide')
                            ->label('Carte large (2 colonnes sur desktop)')
                            ->default(false)
                            ->helperText('Étend la carte sur deux colonnes sur grand écran pour mettre en avant un temps fort.')
                            ->columnSpan(4),
                        TextInput::make('sort_order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0)
                            ->helperText('Ordre d’affichage croissant dans la grille (0 = en premier).')
                            ->columnSpan(4),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Désactiver masque le programme sur le site public sans le supprimer.')
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
                    ->size(48)
                    ->getStateUsing(fn (?ScheduleProgram $record): ?string => is_array($record?->image_url)
                        ? ($record->image_url['fr'] ?? $record->image_url[app()->getLocale()] ?? null)
                        : null),
                TextColumn::make('kind')->label('Type')->badge(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->formatStateUsing(fn ($state, ?ScheduleProgram $record): string => self::translateJson($record?->title)),
                TextColumn::make('day_label')->label('Jour'),
                TextColumn::make('time_label')->label('Horaire'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('kind')
                    ->label('Type')
                    ->options([
                        ScheduleProgram::KIND_DAILY => 'Culte du jour',
                        ScheduleProgram::KIND_WEEKLY => 'Hebdomadaire',
                        ScheduleProgram::KIND_SEMINAR => 'Seminaire',
                        ScheduleProgram::KIND_LIVE => 'Live',
                        ScheduleProgram::KIND_SPECIAL => 'Special',
                    ]),
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
            'index' => Pages\ListSchedulePrograms::route('/'),
            'create' => Pages\CreateScheduleProgram::route('/create'),
            'edit' => Pages\EditScheduleProgram::route('/{record}/edit'),
        ];
    }

    /**
     * @param  mixed  $state  Valeur JSON ou tableau multilingue.
     */
    protected static function translateJson(mixed $state): string
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

        $decoded = json_decode($state, true);

        if (! is_array($decoded)) {
            return $state;
        }

        $locale = app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        return (string) ($decoded[$locale]
            ?? $decoded[$fallback]
            ?? collect($decoded)->first(fn ($item): bool => filled($item))
            ?? '');
    }
}
