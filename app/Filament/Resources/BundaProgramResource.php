<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BundaProgramResource\Pages;
use App\Models\BundaProgram;
use App\Models\Event;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Administration des éditions et contenus Bunda 21 (plan alimentaire, annonces, playlists).
 */
class BundaProgramResource extends Resource
{
    protected static ?string $model = BundaProgram::class;

    protected static ?string $navigationLabel = 'Bunda 21';

    protected static ?string $modelLabel = 'Programme Bunda';

    protected static ?string $pluralModelLabel = 'Programmes Bunda';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Contenu';

    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Édition')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('edition_year')
                            ->label('Année édition')
                            ->numeric()
                            ->required()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->columnSpan(3),
                        TextInput::make('title.fr')
                            ->label('Titre (FR)')
                            ->required()
                            ->columnSpan(9),
                        TextInput::make('subtitle.fr')
                            ->label('Sous-titre (FR)')
                            ->columnSpanFull(),
                        Textarea::make('description.fr')
                            ->label('Résumé court')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('body.fr')
                            ->label('Texte long (page)')
                            ->rows(6)
                            ->columnSpanFull(),
                        FileUpload::make('hero_image.fr')
                            ->label('Image hero')
                            ->image()
                            ->disk('public')
                            ->directory('bunda')
                            ->visibility('public')
                            ->columnSpan(6),
                        Select::make('event_id')
                            ->label('Événement lié (playlist YouTube)')
                            ->options(fn (): array => Event::query()
                                ->where('is_active', true)
                                ->orderByDesc('date_debut')
                                ->get()
                                ->mapWithKeys(fn (Event $event): array => [
                                    $event->id => self::eventOptionLabel($event),
                                ])
                                ->all())
                            ->searchable()
                            ->nullable()
                            ->columnSpan(6),
                    ]),
                Section::make('Plan alimentaire')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextInput::make('meal_plan_label')
                            ->label('Libellé du bouton')
                            ->default('Plan alimentaire')
                            ->columnSpan(4),
                        FileUpload::make('meal_plan_path')
                            ->label('Fichier PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public')
                            ->directory('bunda/plans')
                            ->visibility('public')
                            ->downloadable()
                            ->columnSpan(8),
                    ]),
                Section::make('Annonce prochaine édition')
                    ->description('Carte « À venir » sur la page Bunda et bouton « Être informé ».')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        Toggle::make('is_upcoming_announcement')
                            ->label('Afficher comme prochaine édition')
                            ->columnSpan(4),
                        TextInput::make('upcoming_month_label')
                            ->label('Mois affiché')
                            ->default('Novembre')
                            ->columnSpan(4),
                        Textarea::make('upcoming_description.fr')
                            ->label('Description annonce')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Publication')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        Toggle::make('is_active')->label('Actif')->default(true)->columnSpan(4),
                        TextInput::make('sort_order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('edition_year', 'desc')
            ->columns([
                TextColumn::make('edition_year')->label('Année')->sortable(),
                TextColumn::make('title.fr')->label('Titre')->searchable(),
                TextColumn::make('event.designation')
                    ->label('Événement lié')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) ($state['fr'] ?? '') : (string) $state)
                    ->limit(40),
                ToggleColumn::make('is_upcoming_announcement')->label('À venir'),
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
            'index' => Pages\ListBundaPrograms::route('/'),
            'create' => Pages\CreateBundaProgram::route('/create'),
            'edit' => Pages\EditBundaProgram::route('/{record}/edit'),
        ];
    }

    private static function eventOptionLabel(Event $event): string
    {
        $designation = $event->designation;
        $title = is_array($designation) ? (string) ($designation['fr'] ?? reset($designation) ?: '') : '';

        return $title !== '' ? $title : 'Événement #'.$event->getKey();
    }
}
