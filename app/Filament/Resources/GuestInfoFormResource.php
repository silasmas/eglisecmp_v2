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
use Filament\Forms\Components\Repeater;
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

    public static function form(Schema $schema): Schema
    {
        $departmentOptions = fn (): array => ChurchDepartment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();

        return $schema
            ->columns(12)
            ->schema([
                Section::make('Général')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('project_id')
                            ->label('Projet d’accueil')
                            ->relationship('project', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(6),
                        TextInput::make('title')->label('Titre')->required()->columnSpan(6),
                        TextInput::make('slug')->label('Slug')->helperText('Laissé vide = généré')->columnSpan(4),
                        Toggle::make('is_published')->label('Publié / visible')->default(false)->columnSpan(2),
                        DateTimePicker::make('visible_from')->label('Visible du')->seconds(false)->columnSpan(3),
                        DateTimePicker::make('visible_until')->label('Visible jusqu’au')->seconds(false)->columnSpan(3),
                        TextInput::make('plain_password')
                            ->label('Mot de passe départements')
                            ->password()
                            ->revealable()
                            ->helperText('Obligatoire à la création. Laissez vide pour ne pas changer.')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->columnSpan(6),
                        Textarea::make('intro_html')->label('Introduction (HTML)')->rows(3)->columnSpanFull(),
                        Textarea::make('cmp_info_html')->label('Infos CMP (lecture seule)')->rows(4)->columnSpanFull(),
                    ]),
                Section::make('Apparence')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('design.banner_path')
                            ->label('Bannière')
                            ->image()
                            ->disk('public')
                            ->directory('guest-forms')
                            ->visibility('public')
                            ->columnSpan(6),
                        ColorPicker::make('design.primary_color')->label('Couleur principale')->default('#7b1d3e')->columnSpan(3),
                        ColorPicker::make('design.accent_color')->label('Couleur accent')->default('#ea7e2d')->columnSpan(3),
                        TextInput::make('design.radius')
                            ->label('Rayon (px)')
                            ->numeric()
                            ->default(16)
                            ->columnSpan(3),
                    ]),
                Section::make('Rubriques & questions')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('sections')
                            ->relationship()
                            ->label('Rubriques')
                            ->columnSpanFull()
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->schema([
                                TextInput::make('title')->label('Titre rubrique')->required()->columnSpan(6),
                                Select::make('department_ids')
                                    ->label('Départements (rubrique)')
                                    ->options($departmentOptions)
                                    ->multiple()
                                    ->searchable()
                                    ->columnSpan(6),
                                Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),
                                Repeater::make('fields')
                                    ->relationship()
                                    ->label('Questions')
                                    ->orderColumn('sort_order')
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                    ->schema([
                                        TextInput::make('label')->label('Libellé')->required()->columnSpan(5),
                                        TextInput::make('key')->label('Clé')->helperText('Auto si vide')->columnSpan(3),
                                        Select::make('type')
                                            ->label('Type')
                                            ->options(GuestInfoFormField::typeOptions())
                                            ->required()
                                            ->columnSpan(4),
                                        Select::make('department_ids')
                                            ->label('Départements (override)')
                                            ->options($departmentOptions)
                                            ->multiple()
                                            ->searchable()
                                            ->columnSpan(6),
                                        Toggle::make('required')->label('Obligatoire')->columnSpan(2),
                                        Textarea::make('help_text')->label('Aide')->rows(1)->columnSpan(4),
                                        Textarea::make('options')
                                            ->label('Options JSON (checkbox / food_grid)')
                                            ->rows(3)
                                            ->helperText('Ex. {"choices":{"a":"Libellé"}} — vide = défaut food_grid')
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
                                    ])
                                    ->columns(12)
                                    ->defaultItems(0)
                                    ->addActionLabel('Ajouter une question'),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter une rubrique'),
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
                IconColumn::make('is_published')->label('Publié')->boolean(),
                TextColumn::make('visible_from')->label('Du')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('visible_until')->label('Au')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('sections_count')->counts('sections')->label('Rubriques'),
            ])
            ->actions([
                EditAction::make(),
                Action::make('seedPdf')
                    ->label('Charger template PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Remplacer par le template PDF ?')
                    ->modalDescription('Les rubriques et questions actuelles seront effacées et remplacées.')
                    ->action(function (GuestInfoForm $record): void {
                        $deptIds = $record->project?->departments()->pluck('church_departments.id')->map(fn ($id): int => (int) $id)->all() ?? [];
                        app(GuestInfoFormPdfTemplateService::class)->applyToForm($record, $deptIds);
                        Notification::make()->title('Template PDF chargé')->success()->send();
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
