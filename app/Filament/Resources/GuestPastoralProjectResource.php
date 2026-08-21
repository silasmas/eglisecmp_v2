<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GuestPastoralProjectResource\Pages;
use App\Filament\Resources\GuestPastoralProjectResource\RelationManagers\GuestPastorsRelationManager;
use App\Filament\Resources\GuestPastoralProjectResource\RelationManagers\InviteDispatchesRelationManager;
use App\Models\GuestInviteDispatch;
use App\Models\GuestPastoralProject;
use App\Models\User;
use App\Services\GuestInviteDispatchService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
                                FileUpload::make('photo_path')
                                    ->label('Photo du pasteur')
                                    ->image()
                                    ->avatar()
                                    ->disk('public')
                                    ->directory('guest-pastors')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->columnSpanFull()
                                    ->helperText('Affichée dans le mail, le formulaire public et les réponses départements.'),
                                TextInput::make('full_name')->label('Nom complet')->required()->columnSpan(6),
                                TextInput::make('church_name')->label('Église')->columnSpan(6),
                                TextInput::make('email')->label('E-mail')->email()->columnSpan(4),
                                TextInput::make('phone')->label('Téléphone')->tel()->columnSpan(4),
                                TextInput::make('country')->label('Pays')->columnSpan(4),
                                TextInput::make('city')->label('Ville')->columnSpan(4),
                                DateTimePicker::make('arrival_at')->label('Arrivée probable')->seconds(false)->columnSpan(4),
                                DateTimePicker::make('ministry_at')->label('Date de prestation')->seconds(false)->columnSpan(4),
                                TextInput::make('invite_token')
                                    ->label('Token lien')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Généré automatiquement à l’enregistrement')
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['full_name'] ?? null)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter un pasteur invité'),
                    ]),
            ]);
    }

    /**
     * Formulaire d’action « Envoyer les invitations ».
     *
     * @return array<int, mixed>
     */
    public static function sendInvitesForm(GuestPastoralProject $record): array
    {
        $pastorOptions = $record->guestPastors()
            ->orderBy('full_name')
            ->get()
            ->mapWithKeys(function ($pastor): array {
                $contacts = collect([$pastor->email, $pastor->phone])->filter()->implode(' · ');

                return [
                    $pastor->id => $pastor->full_name.($contacts !== '' ? ' ('.$contacts.')' : ' — sans contact'),
                ];
            })
            ->all();

        return [
            Radio::make('recipient_mode')
                ->label('Destinataires')
                ->options([
                    'all' => 'Tous les pasteurs du projet',
                    'selected' => 'Sélectionner certains pasteurs',
                ])
                ->default('all')
                ->required()
                ->live(),
            CheckboxList::make('pastor_ids')
                ->label('Pasteurs')
                ->options($pastorOptions)
                ->columns(1)
                ->required()
                ->visible(fn (Get $get): bool => $get('recipient_mode') === 'selected')
                ->helperText('Cochez les pasteurs à qui envoyer le lien.'),
            CheckboxList::make('channels')
                ->label('Canaux d’envoi')
                ->options(GuestInviteDispatch::channelOptions())
                ->default([GuestInviteDispatch::CHANNEL_EMAIL])
                ->required()
                ->columns(3)
                ->helperText('WhatsApp ouvre un lien wa.me à valider manuellement. SMS utilise la passerelle configurée.'),
        ];
    }

    /**
     * Exécute l’envoi multi-canal et affiche le résultat.
     *
     * @param  array<string, mixed>  $data
     */
    public static function runSendInvites(GuestPastoralProject $record, array $data): void
    {
        $form = $record->form;
        if ($form === null || ! $form->is_published) {
            Notification::make()
                ->title('Formulaire manquant ou non publié')
                ->body('Créez et publiez d’abord un formulaire pour ce projet.')
                ->danger()
                ->send();

            return;
        }

        $mode = (string) ($data['recipient_mode'] ?? 'all');
        $pastorIds = $mode === 'selected'
            ? array_map('intval', (array) ($data['pastor_ids'] ?? []))
            : [];
        $channels = array_values(array_map('strval', (array) ($data['channels'] ?? [])));

        if ($mode === 'selected' && $pastorIds === []) {
            Notification::make()
                ->title('Aucun pasteur sélectionné')
                ->danger()
                ->send();

            return;
        }

        $actor = auth()->user() instanceof User ? auth()->user() : null;
        $result = app(GuestInviteDispatchService::class)->dispatch($record, $pastorIds, $channels, $actor);

        $bodyParts = [
            "Réussis / prêts : {$result['sent']}",
            "Échecs : {$result['failed']}",
            "Ignorés : {$result['skipped']}",
        ];

        if ($result['whatsapp_links'] !== []) {
            $bodyParts[] = 'Liens WhatsApp à ouvrir :';
            foreach ($result['whatsapp_links'] as $link) {
                $bodyParts[] = '• '.$link['name'].' : '.$link['url'];
            }
        }

        if ($result['messages'] !== []) {
            $bodyParts[] = implode("\n", array_slice($result['messages'], 0, 8));
        }

        Notification::make()
            ->title('Invitations traitées')
            ->body(implode("\n", $bodyParts))
            ->success()
            ->persistent()
            ->send();
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
                    ->modalHeading('Envoyer les invitations')
                    ->modalDescription('Choisissez les pasteurs et les canaux (e-mail, SMS, WhatsApp).')
                    ->form(fn (GuestPastoralProject $record): array => self::sendInvitesForm($record))
                    ->action(function (GuestPastoralProject $record, array $data): void {
                        self::runSendInvites($record, $data);
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            GuestPastorsRelationManager::class,
            InviteDispatchesRelationManager::class,
        ];
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
