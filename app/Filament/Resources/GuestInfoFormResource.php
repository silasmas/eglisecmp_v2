<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GuestInfoFormResource\Pages;
use App\Models\ChurchDepartment;
use App\Models\GuestInfoForm;
use App\Models\GuestInfoFormField;
use App\Services\GuestInfoFormPdfTemplateService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Configuration des formulaires de renseignement (rubriques, design, période).
 */
class GuestInfoFormResource extends Resource
{
    protected static ?string $model = GuestInfoForm::class;

    protected static ?string $navigationLabel = 'Formulaires';

    protected static ?string $modelLabel = 'Formulaire';

    protected static ?string $pluralModelLabel = 'Formulaires';

    protected static ?string $slug = 'guest-info-forms';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Accueil invités';

    protected static ?int $navigationSort = 20;

    /**
     * Options d’affichage public du formulaire.
     *
     * @return array<string, string>
     */
    public static function layoutModeOptions(): array
    {
        return [
            GuestInfoForm::LAYOUT_WIZARD => 'Assistant multi-étapes (recommandé) — une rubrique = une étape',
            GuestInfoForm::LAYOUT_SINGLE => 'Page unique — toutes les rubriques sur un seul écran',
        ];
    }

    /**
     * Schéma d’une question (empilé verticalement pour éviter les champs coupés).
     *
     * @param  callable(): array<int|string, string>  $departmentOptions
     * @return array<int, mixed>
     */
    public static function questionFieldsSchema(callable $departmentOptions): array
    {
        return [
            TextInput::make('label')
                ->label('Libellé de la question')
                ->required()
                ->columnSpanFull(),
            TextInput::make('key')
                ->label('Clé technique')
                ->helperText('Laissée vide = générée automatiquement')
                ->columnSpanFull(),
            Select::make('type')
                ->label('Type de champ')
                ->options(GuestInfoFormField::typeOptions())
                ->required()
                ->native(false)
                ->columnSpanFull(),
            Select::make('department_ids')
                ->label('Départements (override de cette question)')
                ->helperText('Vide = hérite des départements de la rubrique')
                ->options($departmentOptions)
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),
            Toggle::make('required')
                ->label('Champ obligatoire')
                ->inline(false)
                ->columnSpanFull(),
            Textarea::make('help_text')
                ->label('Texte d’aide')
                ->rows(2)
                ->columnSpanFull(),
            Textarea::make('options')
                ->label('Options JSON (checkbox_group / food_grid)')
                ->rows(4)
                ->helperText('Ex. {"choices":{"retro":"Rétroprojecteur"}} — vide + type food_grid = grille PDF par défaut')
                ->columnSpanFull()
                ->formatStateUsing(function ($state): string {
                    if (is_array($state)) {
                        return (string) json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                    }

                    return is_string($state) ? $state : '';
                })
                ->dehydrateStateUsing(function (?string $state): ?array {
                    if ($state === null || trim($state) === '') {
                        return null;
                    }
                    $decoded = json_decode($state, true);

                    return is_array($decoded) ? $decoded : null;
                }),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        $departmentOptions = fn (): array => ChurchDepartment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();

        return $schema
            ->columns(1)
            ->schema([
                Section::make('Démarrage')
                    ->description('Choisissez comment initialiser les questions. Visible uniquement à la création.')
                    ->visibleOn('create')
                    ->schema([
                        Radio::make('bootstrap_template')
                            ->label('Modèle de départ')
                            ->options([
                                'pdf' => 'Modèle fiche PDF (questions du fichier de renseignement) — vous pourrez en ajouter ensuite',
                                'blank' => 'Formulaire vide — je crée moi-même les rubriques et questions',
                            ])
                            ->default('pdf')
                            ->required()
                            ->dehydrated(false),
                    ]),
                Section::make('Parcours du formulaire (étapes)')
                    ->description('Pour un formulaire long (fiche PDF), choisissez l’assistant : chaque rubrique ci-dessous devient une étape.')
                    ->schema([
                        Radio::make('layout_mode')
                            ->label('Mode d’affichage pour le pasteur invité')
                            ->options(self::layoutModeOptions())
                            ->default(GuestInfoForm::LAYOUT_WIZARD)
                            ->required()
                            ->descriptions([
                                GuestInfoForm::LAYOUT_WIZARD => 'Barre de progression, boutons Précédent / Suivant, une rubrique à la fois.',
                                GuestInfoForm::LAYOUT_SINGLE => 'Tout le formulaire sur une seule page (peut être très long).',
                            ]),
                    ]),
                Section::make('Général')
                    ->columns(2)
                    ->schema([
                        Select::make('project_id')
                            ->label('Projet d’accueil')
                            ->relationship('project', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),
                        TextInput::make('title')->label('Titre')->required()->columnSpan(1),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Laissé vide = généré automatiquement')
                            ->columnSpan(1),
                        Toggle::make('is_published')
                            ->label('Publié / visible')
                            ->default(false)
                            ->inline(false)
                            ->columnSpan(1),
                        DateTimePicker::make('visible_from')
                            ->label('Visible du')
                            ->seconds(false)
                            ->columnSpan(1),
                        DateTimePicker::make('visible_until')
                            ->label('Visible jusqu’au')
                            ->seconds(false)
                            ->columnSpan(1),
                        TextInput::make('plain_password')
                            ->label('Mot de passe départements')
                            ->password()
                            ->revealable()
                            ->helperText('À la création un mot de passe est généré si vide. Laissez vide en édition pour ne pas changer.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->columnSpanFull(),
                        RichEditor::make('intro_html')
                            ->label('Introduction')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline'],
                                ['h2', 'h3', 'paragraph'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                        RichEditor::make('cmp_info_html')
                            ->label('Infos CMP (affichées en lecture seule au pasteur)')
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline'],
                                ['h2', 'h3', 'paragraph'],
                                ['bulletList', 'orderedList'],
                                ['link'],
                                ['undo', 'redo'],
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Apparence')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('design.banner_path')
                            ->label('Bannière')
                            ->image()
                            ->disk('public')
                            ->directory('guest-forms')
                            ->visibility('public')
                            ->columnSpanFull(),
                        ColorPicker::make('design.primary_color')
                            ->label('Couleur principale')
                            ->default('#7b1d3e'),
                        ColorPicker::make('design.accent_color')
                            ->label('Couleur accent')
                            ->default('#ea7e2d'),
                        TextInput::make('design.radius')
                            ->label('Rayon des coins (px)')
                            ->numeric()
                            ->default(16)
                            ->columnSpanFull(),
                    ]),
                Section::make('Rubriques & questions')
                    ->description('Chaque rubrique = une étape si le mode Assistant est activé. Utilisez « Aperçu » en haut de page pour voir le rendu.')
                    ->schema([
                        Repeater::make('sections')
                            ->relationship()
                            ->label('Rubriques (= étapes du wizard)')
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Nouvelle rubrique')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Titre de la rubrique / étape')
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('department_ids')
                                    ->label('Départements concernés (rubrique)')
                                    ->options($departmentOptions)
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Repeater::make('fields')
                                    ->relationship()
                                    ->label('Questions de cette étape')
                                    ->orderColumn('sort_order')
                                    ->collapsible()
                                    ->collapsed()
                                    ->cloneable()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Nouvelle question')
                                    ->schema(self::questionFieldsSchema($departmentOptions))
                                    ->columns(1)
                                    ->defaultItems(0)
                                    ->addActionLabel('Ajouter une question')
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter une rubrique / étape')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('project.title')->label('Projet'),
                TextColumn::make('layout_mode')
                    ->label('Parcours')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        GuestInfoForm::LAYOUT_WIZARD => 'Assistant (étapes)',
                        default => 'Page unique',
                    })
                    ->badge()
                    ->color(fn (?string $state): string => $state === GuestInfoForm::LAYOUT_WIZARD ? 'success' : 'gray'),
                IconColumn::make('is_published')->label('Publié')->boolean(),
                TextColumn::make('visible_from')->label('Du')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('visible_until')->label('Au')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('sections_count')->counts('sections')->label('Étapes'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('seedPdf')
                    ->label('Charger template PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Remplacer par le template PDF ?')
                    ->modalDescription('Efface les rubriques/questions actuelles et les remplace par celles de la fiche de renseignements. Le mode Assistant (étapes) sera activé automatiquement.')
                    ->action(function (GuestInfoForm $record): void {
                        $deptIds = $record->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
                        app(GuestInfoFormPdfTemplateService::class)->applyToForm($record, $deptIds);
                        if ($record->layout_mode !== GuestInfoForm::LAYOUT_WIZARD) {
                            $record->update(['layout_mode' => GuestInfoForm::LAYOUT_WIZARD]);
                        }
                        Notification::make()
                            ->title('Template PDF chargé')
                            ->body('Mode Assistant (étapes) activé. Rechargez la page si besoin.')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuestInfoForms::route('/'),
            'create' => Pages\CreateGuestInfoForm::route('/create'),
            'edit' => Pages\EditGuestInfoForm::route('/{record}/edit'),
        ];
    }
}
