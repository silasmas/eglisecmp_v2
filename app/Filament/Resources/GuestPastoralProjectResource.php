<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GuestPastoralProjectResource\Pages;
use App\Mail\GuestPastorInviteMail;
use App\Models\GuestPastor;
use App\Models\GuestPastoralProject;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

/**
 * Projets d’accueil de pasteurs invités (églises sœurs).
 */
class GuestPastoralProjectResource extends Resource
{
    protected static ?string $model = GuestPastoralProject::class;

    protected static ?string $navigationLabel = 'Projets d’accueil';

    protected static ?string $modelLabel = 'Projet d’accueil';

    protected static ?string $pluralModelLabel = 'Projets d’accueil';

    protected static ?string $slug = 'guest-pastoral-projects';

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Accueil invités';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Projet')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')->label('Titre')->required()->columnSpan(8),
                        Select::make('status')
                            ->label('Statut')
                            ->options(GuestPastoralProject::statusOptions())
                            ->required()
                            ->default(GuestPastoralProject::STATUS_DRAFT)
                            ->columnSpan(4),
                        Select::make('event_id')
                            ->label('Événement CMP')
                            ->relationship('event', 'id')
                            ->getOptionLabelFromRecordUsing(function ($record): string {
                                $designation = is_array($record->designation)
                                    ? ($record->designation['fr'] ?? reset($record->designation) ?: '#'.$record->id)
                                    : (string) ($record->designation ?? '#'.$record->id);

                                return $designation;
                            })
                            ->searchable()
                            ->preload()
                            ->columnSpan(6),
                        DateTimePicker::make('starts_at')->label('Début projet / event')->seconds(false)->columnSpan(3),
                        DateTimePicker::make('ends_at')->label('Fin projet / event')->seconds(false)->columnSpan(3),
                        Select::make('departments')
                            ->label('Départements impliqués')
                            ->relationship('departments', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Départements qui préparent l’accueil et recevront les réponses.')
                            ->columnSpanFull(),
                        Textarea::make('notes')->label('Notes internes')->rows(3)->columnSpanFull(),
                    ]),
                Section::make('Pasteurs invités')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('guestPastors')
                            ->relationship()
                            ->label('Invités')
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('full_name')->label('Nom complet')->required()->columnSpan(4),
                                TextInput::make('church_name')->label('Église')->columnSpan(4),
                                TextInput::make('email')->label('E-mail')->email()->columnSpan(4),
                                TextInput::make('phone')->label('Téléphone')->tel()->columnSpan(3),
                                TextInput::make('country')->label('Pays')->columnSpan(3),
                                TextInput::make('city')->label('Ville')->columnSpan(3),
                                DateTimePicker::make('arrival_at')->label('Arrivée probable')->seconds(false)->columnSpan(3),
                                DateTimePicker::make('ministry_at')->label('Date de prestation')->seconds(false)->columnSpan(3),
                                TextInput::make('invite_token')
                                    ->label('Token lien')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Généré automatiquement à l’enregistrement')
                                    ->columnSpan(3),
                            ])
                            ->columns(12)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['full_name'] ?? null)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter un pasteur invité'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Titre')->searchable()->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn (?string $state): string => GuestPastoralProject::statusOptions()[$state] ?? ($state ?? '—'))
                    ->badge(),
                TextColumn::make('starts_at')->label('Début')->dateTime('d/m/Y')->placeholder('—'),
                TextColumn::make('ends_at')->label('Fin')->dateTime('d/m/Y')->placeholder('—'),
                TextColumn::make('guest_pastors_count')->counts('guestPastors')->label('Invités'),
                TextColumn::make('departments.name')->label('Départements')->badge()->limitList(3),
            ])
            ->actions([
                EditAction::make(),
                Action::make('sendInvites')
                    ->label('Envoyer les liens')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer les liens aux pasteurs invités')
                    ->modalDescription('Un e-mail avec le lien du formulaire sera envoyé à chaque invité ayant une adresse e-mail.')
                    ->action(function (GuestPastoralProject $record): void {
                        $form = $record->form;
                        if ($form === null || ! $form->is_published) {
                            Notification::make()
                                ->title('Formulaire manquant ou non publié')
                                ->body('Créez et publiez d’abord un formulaire pour ce projet.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $sent = 0;
                        foreach ($record->guestPastors as $pastor) {
                            if (! filled($pastor->email)) {
                                continue;
                            }
                            Mail::to($pastor->email)->send(new GuestPastorInviteMail(
                                $pastor,
                                $pastor->publicFormUrl(),
                                $form->title,
                            ));
                            $sent++;
                        }

                        Notification::make()
                            ->title($sent > 0 ? "{$sent} invitation(s) envoyée(s)" : 'Aucun e-mail envoyé')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuestPastoralProjects::route('/'),
            'create' => Pages\CreateGuestPastoralProject::route('/create'),
            'edit' => Pages\EditGuestPastoralProject::route('/{record}/edit'),
        ];
    }
}
