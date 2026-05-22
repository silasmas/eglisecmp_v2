<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Event;
use App\Models\Minister;
use App\Models\Post;
use App\Support\FilamentImageUrl;
use App\Support\YoutubeDurationParser;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use Oliwol\FilamentRichEditorHeroicons\FilamentRichEditorHeroicons;
use TinusG\FilamentHoverImageColumn\HoverImageColumn as ImageColumn;
use UnitEnum;

class PostResource extends Resource
{
    use HasTabbedActions;

    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Informations principales')
                    ->description('Identite du post, classification et publication.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title.fr')
                            ->label('Theme')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, ?string $state, callable $set): mixed => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null)
                            ->columnSpan(8),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Post::class, 'slug', ignoreRecord: true)
                            ->columnSpan(4),
                        Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                1 => 'Video',
                                2 => 'Audio',
                                3 => 'Article',
                            ])
                            ->columnSpan(4),
                        Select::make('event_id')
                            ->label('Evenement')
                            ->relationship('event', 'designation')
                            ->getOptionLabelFromRecordUsing(fn (Event $record): string => static::translateState($record->designation) ?: "Evenement #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                        Select::make('weekly_service_day')
                            ->label('Jour de culte hebdomadaire (optionnel)')
                            ->options([
                                'mercredi' => 'Mercredi',
                                'jeudi' => 'Jeudi',
                                'dimanche' => 'Dimanche',
                            ])
                            ->native(false)
                            ->nullable()
                            ->columnSpan(4),
                        Select::make('minister_id')
                            ->label('Predicateur')
                            ->relationship('minister', 'fullname')
                            ->getOptionLabelFromRecordUsing(fn (Minister $record): string => static::translateState($record->fullname) ?: "Predicateur #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->columnSpan(4),
                        TextInput::make('author')->label('Auteur')->maxLength(191)->columnSpan(6),
                        DateTimePicker::make('date_publication')->label('Date de publication')->columnSpan(6),
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(4),
                    ]),
                Section::make('Media et video')
                    ->description('Ajoute la vignette et verifie la video YouTube avant enregistrement.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('link_url')
                            ->label('Lien YouTube')
                            ->placeholder('URL complete ou ID video (ex: fBQpJheSFyo)')
                            ->helperText('Colle un lien YouTube (watch, youtu.be, shorts, embed) ou directement l\'ID video.')
                            ->live()
                            ->columnSpan(6),
                        Placeholder::make('youtube_duration_detected')
                            ->label('Duree detectee (YouTube)')
                            ->content(function (?Post $record): string {
                                $seconds = $record?->youtube_duration_seconds;

                                if ($seconds === null || (int) $seconds <= 0) {
                                    return '— (renseignez YOUTUBE_API_KEY cote serveur pour recuperer automatiquement la duree)';
                                }

                                return YoutubeDurationParser::formatFrench((int) $seconds);
                            })
                            ->columnSpan(6),
                        TextInput::make('references.fr')->label('Reference')->columnSpan(12),
                        Placeholder::make('youtube_preview')
                            ->label('Previsualisation video')
                            ->content(function (Get $get): HtmlString|string {
                                $embedUrl = static::buildYoutubeEmbedUrl($get('link_url'));

                                if (! $embedUrl) {
                                    return 'Aucune video detectee pour le moment.';
                                }

                                return new HtmlString('<div style="position:relative;padding-top:56.25%;border-radius:12px;overflow:hidden;"><iframe src="'.e($embedUrl).'" title="YouTube preview" style="position:absolute;inset:0;width:100%;height:100%;border:0;" allowfullscreen></iframe></div>');
                            })
                            ->columnSpanFull(),
                        FileUpload::make('image_url.fr')
                            ->label('Vignette')
                            ->image()
                            ->disk('public')
                            ->directory('posts')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),
                Section::make('Contenu editorial')
                    ->description('Renseigne le texte du message et les notes complementaires.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('body.fr')
                            ->label('Contenu')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'textColor', 'highlight'],
                                ['h1', 'h2', 'h3', 'paragraph', 'small', 'lead'],
                                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                                ['blockquote', 'code', 'codeBlock', 'bulletList', 'orderedList', 'horizontalRule'],
                                ['table', 'attachFiles', 'addHeroicon', 'undo', 'redo', 'clearFormatting'],
                            ])
                            ->plugins([
                                FilamentRichEditorHeroicons::make(),
                            ])
                            ->columnSpanFull(),
                        TextInput::make('observation.fr')->label('Observation')->columnSpanFull(),
                    ]),
                Section::make('Mise en avant accueil')
                    ->description('Carte « Contenu a la une » : dates optionnelles pour planifier la plage de diffusion.')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('featured_on_home')->label('Afficher a la une sur l accueil')->columnSpan(4),
                        DateTimePicker::make('featured_from')->label('Debut mise en avant')->seconds(false)->columnSpan(4),
                        DateTimePicker::make('featured_until')->label('Fin mise en avant')->seconds(false)->columnSpan(4),
                        TextInput::make('featured_sort_order')->label('Ordre d affichage')->numeric()->default(0)->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['event', 'minister']))
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Vignette')
                    ->circular(false)
                    ->square()
                    ->size(48)
                    ->getStateUsing(fn (?Post $record): ?string => FilamentImageUrl::resolve($record?->image_url))
                    ->placeholder('—'),
                ImageColumn::make('speaker_avatar')
                    ->label('Photo orateur')
                    ->circular()
                    ->size(40)
                    ->getStateUsing(fn (?Post $record): ?string => FilamentImageUrl::resolve($record?->minister?->image_url))
                    ->placeholder('—')
                    ->visible(fn (?Post $record): bool => filled($record?->minister_id)),
                TextColumn::make('speaker_name')
                    ->label('Orateur')
                    ->getStateUsing(function (?Post $record): string {
                        if (! $record) {
                            return 'Inconnu';
                        }

                        if (filled($record->minister_id)) {
                            return $record->getSpeakerName();
                        }

                        return (string) ($record->author ?: 'Inconnu');
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where('author', 'like', "%{$search}%")
                                ->orWhereHas('minister', fn (Builder $ministerQuery): Builder => $ministerQuery->where('fullname', 'like', "%{$search}%"));
                        });
                    }),
                TextColumn::make('title')
                    ->label('Theme')
                    ->searchable()
                    ->sortable()
                    ->limit(28)
                    ->tooltip(fn (?Post $record): ?string => $record?->getLocalizedValue('title'))
                    ->formatStateUsing(fn ($state, ?Post $record): string => (string) ($record?->getLocalizedValue('title') ?? '')),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state): string => match ((int) $state) {
                        1 => 'danger',
                        2 => 'info',
                        3 => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ((int) $state) {
                        1 => 'Video',
                        2 => 'Audio',
                        3 => 'Article',
                        default => (string) $state,
                    }),
                SelectColumn::make('weekly_service_day')
                    ->label('Culte de la semaine')
                    ->options([
                        'mercredi' => 'Mercredi',
                        'jeudi' => 'Jeudi',
                        'dimanche' => 'Dimanche',
                    ])
                    ->placeholder('Non defini')
                    ->native(false)
                    ->toggleable(),
                SelectColumn::make('event_id')
                    ->label('Evenement')
                    ->placeholder('Aucun')
                    ->options(function (): array {
                        return Event::query()
                            ->orderByDesc('date_debut')
                            ->limit(300)
                            ->get()
                            ->mapWithKeys(function (Event $event): array {
                                return [
                                    (string) $event->getKey() => static::translateState($event->designation) ?: "Evenement #{$event->getKey()}",
                                ];
                            })
                            ->all();
                    })
                    ->searchableOptions()
                    ->native(false)
                    ->toggleable(),
                TextColumn::make('date_publication')
                    ->label('Publication')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => filled($state)
                        ? Carbon::parse($state)->locale('fr')->translatedFormat('d M Y H:i')
                        : '-'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                IconColumn::make('featured_on_home')->label('Une')->boolean(),
                TextColumn::make('featured_sort_order')->label('Ordre une')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Group::make('event_id')
                    ->label('Evenement')
                    ->getTitleFromRecordUsing(fn (?Post $record): string => static::translateState($record?->event?->designation) ?: 'Sans evenement'),
                Group::make('type')
                    ->label('Type')
                    ->getTitleFromRecordUsing(fn (?Post $record): string => match ((int) ($record?->type ?? 0)) {
                        1 => 'Video',
                        2 => 'Audio',
                        3 => 'Article',
                        default => 'Autre',
                    }),
            ])
            ->defaultGroup('event_id')
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        1 => 'Video',
                        2 => 'Audio',
                        3 => 'Article',
                    ]),
                SelectFilter::make('weekly_service_day')
                    ->label('Jour de culte')
                    ->options([
                        'mercredi' => 'Mercredi',
                        'jeudi' => 'Jeudi',
                        'dimanche' => 'Dimanche',
                    ]),
                SelectFilter::make('event_id')
                    ->label('Evenement')
                    ->relationship('event', 'designation'),
                TernaryFilter::make('is_active')->label('Actif'),
                TernaryFilter::make('featured_on_home')->label('A la une accueil'),
            ])
            ->defaultSort('date_publication', 'desc')
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
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

        return (string) ($decoded[$locale]
            ?? $decoded[$fallback]
            ?? collect($decoded)->first(fn ($item): bool => filled($item))
            ?? '');
    }

    protected static function buildYoutubeEmbedUrl(mixed $value): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value) === 1) {
            return "https://www.youtube.com/embed/{$value}";
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?v=)([A-Za-z0-9_-]{11})/i',
            '/(?:youtube\.com\/shorts\/)([A-Za-z0-9_-]{11})/i',
            '/(?:youtube\.com\/embed\/)([A-Za-z0-9_-]{11})/i',
            '/(?:youtu\.be\/)([A-Za-z0-9_-]{11})/i',
            '/(?:v=)([A-Za-z0-9_-]{11})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value, $matches) === 1) {
                return "https://www.youtube.com/embed/{$matches[1]}";
            }
        }

        return null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
