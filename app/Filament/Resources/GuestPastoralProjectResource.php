<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\GuestPastoralProjectResource\Pages;
use App\Filament\Resources\GuestPastoralProjectResource\RelationManagers\GuestPastorsRelationManager;
use App\Filament\Resources\GuestPastoralProjectResource\RelationManagers\InviteDispatchesRelationManager;
use App\Models\GuestInviteDispatch;
use App\Models\GuestPastor;
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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
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
     * Assistant d’envoi : sélection puis aperçu éditable par canal.
     * Le record est injecté par Filament (table ou page d’édition).
     */
    public static function sendInvitesAction(): Action
    {
        return Action::make('sendInvites')
            ->label('Envoyer les invitations')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->modalHeading('Envoyer les invitations')
            ->modalWidth('5xl')
            ->closeModalByClickingAway(false)
            ->steps(fn (GuestPastoralProject $record): array => self::buildSendInviteWizardSteps($record))
            ->modalSubmitActionLabel('Fermer')
            ->action(function (): void {
                // L’envoi se fait canal par canal via les boutons de l’aperçu.
            });
    }

    /**
     * Étapes de l’assistant d’invitation pour un projet.
     *
     * @return array<int, Step>
     */
    public static function buildSendInviteWizardSteps(GuestPastoralProject $record): array
    {
        $service = app(GuestInviteDispatchService::class);
        $formTitle = $record->form?->title ?? 'fiche';
        $defaults = $service->defaultMessageTemplates($record, $formTitle);

        $pastorOptions = $record->guestPastors()
            ->orderBy('full_name')
            ->get()
            ->mapWithKeys(function (GuestPastor $pastor): array {
                $contacts = collect([$pastor->email, $pastor->phone])->filter()->implode(' · ');

                return [
                    $pastor->id => $pastor->full_name.($contacts !== '' ? ' ('.$contacts.')' : ' — sans contact'),
                ];
            })
            ->all();

        return [
            Step::make('Destinataires')
                ->description('Qui reçoit quoi')
                ->schema([
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
                        ->label('Canaux à préparer')
                        ->options(GuestInviteDispatch::channelOptions())
                        ->default([GuestInviteDispatch::CHANNEL_EMAIL])
                        ->required()
                        ->columns(3)
                        ->helperText('À l’étape suivante : aperçu, modification et envoi canal par canal.'),
                ])
                ->afterValidation(function (Get $get, Set $set) use ($defaults): void {
                    if (blank($get('email_subject'))) {
                        $set('email_subject', $defaults['email_subject']);
                    }
                    if (blank($get('email_intro'))) {
                        $set('email_intro', $defaults['email_intro']);
                    }
                    if (blank($get('sms_message'))) {
                        $set('sms_message', $defaults['sms_message']);
                    }
                    if (blank($get('whatsapp_message'))) {
                        $set('whatsapp_message', $defaults['whatsapp_message']);
                    }
                }),
            Step::make('Aperçu & envoi')
                ->description('Modifiez puis validez chaque canal')
                ->schema([
                    Placeholder::make('placeholders_help')
                        ->label('Variables disponibles')
                        ->content(new HtmlString(
                            '<code>{nom}</code> · <code>{lien}</code> (lien court) · <code>{fiche}</code> · <code>{projet}</code>'
                        )),
                    Placeholder::make('sample_preview')
                        ->label('Aperçu (1er pasteur sélectionné)')
                        ->content(function (Get $get) use ($record, $service, $formTitle): HtmlString {
                            $pastor = self::resolvePastorsForSend($record, $get)->first();
                            if (! $pastor instanceof GuestPastor) {
                                return new HtmlString('<em>Aucun pasteur sélectionné.</em>');
                            }

                            $link = e($pastor->shortFormUrl());
                            $channels = (array) ($get('channels') ?? []);
                            $parts = ['<p><strong>'.e($pastor->full_name).'</strong> — lien court : <a href="'.$link.'" target="_blank">'.$link.'</a></p>'];

                            if (in_array(GuestInviteDispatch::CHANNEL_EMAIL, $channels, true)) {
                                $parts[] = '<p><u>E-mail</u><br><em>'.e((string) $get('email_subject')).'</em><br>'
                                    .nl2br(e($service->renderTemplate((string) $get('email_intro'), $pastor, $formTitle, $record->title)))
                                    .'</p>';
                            }
                            if (in_array(GuestInviteDispatch::CHANNEL_SMS, $channels, true)) {
                                $sms = $service->renderTemplate((string) $get('sms_message'), $pastor, $formTitle, $record->title);
                                $parts[] = '<p><u>SMS</u><br>'.nl2br(e($sms)).'</p>';
                            }
                            if (in_array(GuestInviteDispatch::CHANNEL_WHATSAPP, $channels, true)) {
                                $wa = $service->renderTemplate((string) $get('whatsapp_message'), $pastor, $formTitle, $record->title);
                                $url = $service->buildWhatsAppUrl($pastor, $wa);
                                $parts[] = '<p><u>WhatsApp</u><br>'.nl2br(e($wa)).'</p>';
                                if ($url) {
                                    $parts[] = '<p><a href="'.e($url).'" target="_blank" rel="noopener" style="color:#128c7e;font-weight:700;text-decoration:underline;">Ouvrir WhatsApp (aperçu)</a></p>';
                                }
                            }

                            return new HtmlString(implode('', $parts));
                        }),
                    TextInput::make('email_subject')
                        ->label('Objet e-mail')
                        ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_EMAIL, (array) $get('channels'), true))
                        ->live(onBlur: true),
                    Textarea::make('email_intro')
                        ->label('Message e-mail (modifiable)')
                        ->rows(5)
                        ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_EMAIL, (array) $get('channels'), true))
                        ->live(onBlur: true),
                    Textarea::make('sms_message')
                        ->label('Message SMS (modifiable)')
                        ->rows(3)
                        ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_SMS, (array) $get('channels'), true))
                        ->live(debounce: 400)
                        ->helperText('Soyez clair : précisez qu’il s’agit de la fiche de renseignements pasteur invité CMP.'),
                    Placeholder::make('sms_stats')
                        ->label('Compteur SMS')
                        ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_SMS, (array) $get('channels'), true))
                        ->content(function (Get $get) use ($record, $service, $formTitle): HtmlString {
                            $pastor = self::resolvePastorsForSend($record, $get)->first();
                            $template = (string) ($get('sms_message') ?? '');
                            $body = $pastor instanceof GuestPastor
                                ? $service->renderTemplate($template, $pastor, $formTitle, $record->title)
                                : $template;
                            $est = $service->estimateSms($body);
                            $color = $est['segments'] > 1 ? '#b45309' : '#047857';

                            return new HtmlString(
                                '<p style="margin:0;color:'.$color.';font-weight:600;">'
                                .$est['length'].' caractère(s) · <strong>'.$est['segments'].' SMS</strong>'
                                .' (max '.$est['max'].' / segment après normalisation)</p>'
                                .'<p style="margin:0.4rem 0 0;font-size:0.85rem;color:#6b7280;">Aperçu normalisé :<br>'
                                .e($est['preview']).'</p>'
                            );
                        }),
                    Textarea::make('whatsapp_message')
                        ->label('Message WhatsApp (modifiable)')
                        ->rows(5)
                        ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_WHATSAPP, (array) $get('channels'), true))
                        ->live(onBlur: true),
                    SchemaActions::make([
                        Action::make('dispatchEmail')
                            ->label('Envoyer e-mail')
                            ->icon('heroicon-o-envelope')
                            ->color('primary')
                            ->cancelParentActions(false)
                            ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_EMAIL, (array) $get('channels'), true))
                            ->action(function (Get $get) use ($record): void {
                                self::dispatchSingleChannel($record, $get, GuestInviteDispatch::CHANNEL_EMAIL);
                            }),
                        Action::make('dispatchSms')
                            ->label('Envoyer SMS')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->color('warning')
                            ->cancelParentActions(false)
                            ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_SMS, (array) $get('channels'), true))
                            ->action(function (Get $get) use ($record): void {
                                self::dispatchSingleChannel($record, $get, GuestInviteDispatch::CHANNEL_SMS);
                            }),
                        Action::make('dispatchWhatsApp')
                            ->label('Préparer WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('success')
                            ->cancelParentActions(false)
                            ->visible(fn (Get $get): bool => in_array(GuestInviteDispatch::CHANNEL_WHATSAPP, (array) $get('channels'), true))
                            ->action(function (Get $get, Set $set) use ($record): void {
                                $html = self::dispatchSingleChannel($record, $get, GuestInviteDispatch::CHANNEL_WHATSAPP);
                                if ($html !== null) {
                                    $set('whatsapp_links_html', (string) $html);
                                }
                            }),
                    ])->fullWidth(),
                    Placeholder::make('whatsapp_links_box')
                        ->label('Liens WhatsApp cliquables')
                        ->visible(fn (Get $get): bool => filled($get('whatsapp_links_html')))
                        ->content(fn (Get $get): HtmlString => new HtmlString((string) $get('whatsapp_links_html'))),
                    Textarea::make('whatsapp_links_html')
                        ->hidden()
                        ->dehydrated(false),
                ]),
        ];
    }

    /**
     * Pasteurs ciblés selon le mode / la sélection du formulaire d’action.
     *
     * @return \Illuminate\Support\Collection<int, GuestPastor>
     */
    public static function resolvePastorsForSend(GuestPastoralProject $record, Get $get): \Illuminate\Support\Collection
    {
        $mode = (string) ($get('recipient_mode') ?? 'all');
        $query = $record->guestPastors()->orderBy('full_name');

        if ($mode === 'selected') {
            $ids = array_map('intval', (array) ($get('pastor_ids') ?? []));
            if ($ids === []) {
                return collect();
            }
            $query->whereIn('id', $ids);
        }

        return $query->get();
    }

    /**
     * Envoie un canal et affiche le résultat (liens WhatsApp HTML si besoin).
     */
    public static function dispatchSingleChannel(GuestPastoralProject $record, Get $get, string $channel): ?HtmlString
    {
        $form = $record->form;
        if ($form === null || ! $form->is_published) {
            Notification::make()
                ->title('Formulaire manquant ou non publié')
                ->danger()
                ->send();

            return null;
        }

        $mode = (string) ($get('recipient_mode') ?? 'all');
        $pastorIds = $mode === 'selected'
            ? array_map('intval', (array) ($get('pastor_ids') ?? []))
            : [];

        if ($mode === 'selected' && $pastorIds === []) {
            Notification::make()->title('Aucun pasteur sélectionné')->danger()->send();

            return null;
        }

        $actor = auth()->user() instanceof User ? auth()->user() : null;
        $result = app(GuestInviteDispatchService::class)->dispatchChannel(
            $record,
            $pastorIds,
            $channel,
            $actor,
            emailSubject: (string) ($get('email_subject') ?? ''),
            emailIntro: (string) ($get('email_intro') ?? ''),
            smsMessage: (string) ($get('sms_message') ?? ''),
            whatsappMessage: (string) ($get('whatsapp_message') ?? ''),
        );

        $channelLabel = GuestInviteDispatch::channelOptions()[$channel] ?? $channel;
        $stats = "{$channelLabel} — réussis : {$result['sent']} · échecs : {$result['failed']} · ignorés : {$result['skipped']}";
        if ($result['messages'] !== []) {
            $stats .= "\n".implode("\n", array_slice($result['messages'], 0, 6));
        }

        $body = $stats;
        if ($result['whatsapp_links'] !== []) {
            $body .= "\n\nLiens WhatsApp (copiez ou ouvrez depuis l’aperçu) :";
            foreach ($result['whatsapp_links'] as $link) {
                $body .= "\n• ".$link['name'].' : '.$link['url'];
            }
        }

        Notification::make()
            ->title($channelLabel.' traité')
            ->body($body)
            ->success()
            ->persistent()
            ->send();

        return $result['whatsapp_html'];
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
                self::sendInvitesAction(),
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
