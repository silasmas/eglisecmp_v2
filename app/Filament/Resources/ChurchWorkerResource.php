<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\ProvidesAdminTourStep;
use App\Filament\Resources\ChurchWorkerResource\Pages;
use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use App\Models\User;
use App\Services\ChurchWorkerApprovalService;
use App\Services\ChurchWorkerEditLinkNotifyService;
use App\Support\FilamentImageUrl;
use App\Support\KinshasaCommunes;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use TinusG\FilamentHoverImageColumn\HoverImageColumn as ImageColumn;
use UnitEnum;

/**
 * Validation des ouvriers et génération de badges.
 */
class ChurchWorkerResource extends Resource
{
    use HasTabbedActions;
    use ProvidesAdminTourStep;

    protected static ?string $model = ChurchWorker::class;

    protected static ?string $navigationLabel = 'Ouvriers';

    protected static ?string $modelLabel = 'Ouvrier';

    protected static ?string $pluralModelLabel = 'Ouvriers';

    protected static ?string $recordTitleAttribute = 'last_name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Ouvriers';

    protected static ?int $navigationSort = 11;

    /**
     * Requête de base (droits admin / responsable de département).
     *
     * @return Builder<ChurchWorker>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['department', 'user']);

        $user = auth()->user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin') || $user->can('ViewAny:ChurchWorker')) {
            return $query;
        }

        $managedIds = ChurchDepartment::query()
            ->where('manager_user_id', $user->id)
            ->pluck('id');

        return $query->whereIn('department_id', $managedIds);
    }

    /**
     * Accès à la ressource.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        if ($user->can('ViewAny:ChurchWorker') || $user->hasRole('super_admin')) {
            return true;
        }

        return ChurchDepartment::query()->where('manager_user_id', $user->id)->exists();
    }

    /**
     * Formulaire d’édition (dossier complet + photo).
     */
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Photo')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('photo_path')
                            ->label('Photo d’identité')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('workers/photos')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('JPG, PNG ou WebP — max. 5 Mo.')
                            ->columnSpan(4),
                    ]),
                Section::make('Identité')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('department_id')
                            ->label('Département')
                            ->relationship('department', 'name')
                            ->required()
                            ->searchable()
                            ->columnSpan(6),
                        TextInput::make('first_name')->label('Prénom')->required()->columnSpan(3),
                        TextInput::make('last_name')->label('Nom')->required()->columnSpan(3),
                        Select::make('gender')
                            ->label('Sexe')
                            ->options(ChurchWorker::genderOptions())
                            ->required()
                            ->columnSpan(3),
                        DatePicker::make('birth_date')
                            ->label('Naissance')
                            ->required()
                            ->native(false)
                            ->columnSpan(3),
                        TextInput::make('phone')->label('Téléphone')->required()->columnSpan(3),
                        TextInput::make('email')->label('E-mail')->email()->required()->columnSpan(3),
                        TextInput::make('department_role')
                            ->label('Rôle dans le département')
                            ->columnSpan(6),
                        DatePicker::make('department_joined_at')
                            ->label('Date d’intégration')
                            ->native(false)
                            ->columnSpan(6),
                    ]),
                Section::make('Adresse')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('city')
                            ->label('Ville')
                            ->options(['Kinshasa' => 'Kinshasa'])
                            ->required()
                            ->columnSpan(3),
                        Select::make('commune')
                            ->label('Commune')
                            ->options(array_combine(KinshasaCommunes::all(), KinshasaCommunes::all()))
                            ->searchable()
                            ->required()
                            ->columnSpan(3),
                        TextInput::make('quartier')->label('Quartier')->required()->columnSpan(3),
                        TextInput::make('avenue')->label('Avenue')->required()->columnSpan(3),
                        TextInput::make('address_reference')
                            ->label('Référence adresse')
                            ->columnSpanFull(),
                    ]),
                Section::make('Profil professionnel')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('profession')->label('Profession')->columnSpan(4),
                        Select::make('education_level')
                            ->label('Niveau d’étude')
                            ->options(array_combine(
                                ChurchWorker::educationLevelOptions(),
                                ChurchWorker::educationLevelOptions(),
                            ))
                            ->columnSpan(4),
                        TextInput::make('studies')->label('Études')->columnSpan(4),
                        Textarea::make('skills')->label('Compétences')->rows(3)->columnSpanFull(),
                    ]),
                Section::make('Suivi admin')
                    ->columns(12)
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        Select::make('status')
                            ->label('Statut')
                            ->options(ChurchWorker::statusOptions())
                            ->required()
                            ->columnSpan(4),
                        Textarea::make('rejection_reason')
                            ->label('Motif de refus')
                            ->rows(2)
                            ->columnSpan(8),
                    ]),
            ]);
    }

    /**
     * Fiche détaillée affichée dans la modale « Voir ».
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Photo & identité')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        ImageEntry::make('photo_path')
                            ->label('Photo')
                            ->circular()
                            ->imageHeight(140)
                            ->getStateUsing(fn (ChurchWorker $record): ?string => FilamentImageUrl::resolve($record->photo_path))
                            ->columnSpan(3),
                        TextEntry::make('full_name')
                            ->label('Nom complet')
                            ->state(fn (ChurchWorker $record): string => $record->fullName())
                            ->columnSpan(5),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->formatStateUsing(fn (?string $state): string => ChurchWorker::statusOptions()[$state ?? ''] ?? ($state ?? '—'))
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                ChurchWorker::STATUS_APPROVED => 'success',
                                ChurchWorker::STATUS_REJECTED => 'danger',
                                default => 'warning',
                            })
                            ->columnSpan(4),
                        TextEntry::make('gender')
                            ->label('Sexe')
                            ->formatStateUsing(fn (?string $state): string => ChurchWorker::genderOptions()[$state ?? ''] ?? '—')
                            ->columnSpan(4),
                        TextEntry::make('birth_date')
                            ->label('Naissance')
                            ->date('d/m/Y')
                            ->columnSpan(4),
                        TextEntry::make('department.name')
                            ->label('Département')
                            ->columnSpan(4),
                        TextEntry::make('department_role')
                            ->label('Rôle')
                            ->placeholder('—')
                            ->columnSpan(6),
                        TextEntry::make('department_joined_at')
                            ->label('Depuis')
                            ->date('d/m/Y')
                            ->placeholder('—')
                            ->columnSpan(6),
                    ]),
                Section::make('Coordonnées')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('phone')->label('Téléphone')->columnSpan(4),
                        TextEntry::make('email')->label('E-mail')->placeholder('—')->columnSpan(4),
                        TextEntry::make('user.email')->label('Compte user')->placeholder('—')->columnSpan(4),
                        TextEntry::make('city')->label('Ville')->columnSpan(3),
                        TextEntry::make('commune')->label('Commune')->columnSpan(3),
                        TextEntry::make('quartier')->label('Quartier')->columnSpan(3),
                        TextEntry::make('avenue')->label('Avenue')->columnSpan(3),
                        TextEntry::make('address_reference')
                            ->label('Référence adresse')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Profil')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('profession')->label('Profession')->placeholder('—')->columnSpan(4),
                        TextEntry::make('education_level')->label('Niveau')->placeholder('—')->columnSpan(4),
                        TextEntry::make('studies')->label('Études')->placeholder('—')->columnSpan(4),
                        TextEntry::make('skills')->label('Compétences')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('rejection_reason')
                            ->label('Motif de refus')
                            ->placeholder('—')
                            ->visible(fn (ChurchWorker $record): bool => $record->status === ChurchWorker::STATUS_REJECTED)
                            ->columnSpanFull(),
                    ]),
                Section::make('Badge')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('badge_generated')
                            ->label('Badge')
                            ->formatStateUsing(fn (?bool $state): string => $state ? 'Généré / validé' : 'Non généré')
                            ->badge()
                            ->color(fn (?bool $state): string => $state ? 'success' : 'gray')
                            ->columnSpan(4),
                        TextEntry::make('badge_generated_at')
                            ->label('Généré le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—')
                            ->columnSpan(4),
                        TextEntry::make('badge_token')
                            ->label('Lien badge')
                            ->formatStateUsing(fn (?string $state): string => filled($state)
                                ? route('workers.badge.public', ['token' => $state])
                                : '—')
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('edit_token')
                            ->label('Lien modification ouvrier')
                            ->state(fn (ChurchWorker $record): string => $record->hasValidEditToken()
                                ? ($record->profileEditUrl() ?? '—')
                                : 'Aucun lien actif — générez-en un')
                            ->copyable(fn (ChurchWorker $record): bool => $record->hasValidEditToken())
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Table liste + actions (voir = modale).
     */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->circular()
                    ->getStateUsing(fn (ChurchWorker $record): ?string => FilamentImageUrl::resolve($record->photo_path)),
                TextColumn::make('first_name')->label('Prénom')->searchable(),
                TextColumn::make('last_name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('department.name')->label('Département')->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ChurchWorker::statusOptions()[$state ?? ''] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => match ($state) {
                        ChurchWorker::STATUS_APPROVED => 'success',
                        ChurchWorker::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('badge_generated')
                    ->label('Badge')
                    ->formatStateUsing(fn (?bool $state): string => $state ? 'Oui' : 'Non')
                    ->badge()
                    ->color(fn (?bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('Soumis le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ChurchWorker::statusOptions()),
                SelectFilter::make('department_id')->label('Département')->relationship('department', 'name'),
            ])
            ->recordUrl(null)
            ->actions([
                ViewAction::make()
                    ->label('Voir')
                    ->slideOver()
                    ->modalWidth(Width::FourExtraLarge)
                    ->modalHeading(fn (ChurchWorker $record): string => $record->fullName())
                    ->extraModalFooterActions(fn (ViewAction $action): array => [
                        self::makeApproveAction()->cancelParentActions(),
                        self::makeRejectAction()->cancelParentActions(),
                        self::makeGenerateBadgeAction()->cancelParentActions(),
                        self::makeGenerateEditLinkAction()->cancelParentActions(),
                        self::makeNotifyEditLinkAction()->cancelParentActions(),
                        self::makeOpenBadgeAction(),
                        EditAction::make()
                            ->label('Modifier')
                            ->icon('heroicon-o-pencil-square')
                            ->cancelParentActions(),
                    ]),
                self::makeApproveAction(),
                self::makeRejectAction(),
                self::makeGenerateBadgeAction(),
                self::makeGenerateEditLinkAction(),
                self::makeNotifyEditLinkAction(),
                self::makeOpenBadgeAction(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalHeading('Supprimer l’ouvrier')
                    ->modalDescription('Le dossier et les tokens associés seront définitivement supprimés.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    self::makeNotifyEditLinkBulkAction(),
                    DeleteBulkAction::make()
                        ->label('Supprimer la sélection')
                        ->modalHeading('Supprimer les ouvriers sélectionnés')
                        ->modalDescription('Les dossiers et tokens associés seront définitivement supprimés. Cette action est irréversible.')
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    /**
     * Valide un ouvrier en attente.
     */
    protected static function makeApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Valider')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (ChurchWorker $record): bool => $record->status === ChurchWorker::STATUS_PENDING)
            ->requiresConfirmation()
            ->modalHeading('Valider cet ouvrier ?')
            ->modalDescription('Un compte utilisateur sera créé et un e-mail de confirmation pourra être envoyé.')
            ->action(function (ChurchWorker $record): void {
                $user = auth()->user();
                if (! $user instanceof User) {
                    return;
                }
                app(ChurchWorkerApprovalService::class)->approve($record, $user);
                Notification::make()->title('Ouvrier validé et compte créé')->success()->send();
            });
    }

    /**
     * Refuse une inscription en attente.
     */
    protected static function makeRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Rejeter')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (ChurchWorker $record): bool => $record->status === ChurchWorker::STATUS_PENDING)
            ->form([
                Textarea::make('rejection_reason')->label('Motif')->required()->rows(3),
            ])
            ->action(function (ChurchWorker $record, array $data): void {
                $user = auth()->user();
                if (! $user instanceof User) {
                    return;
                }
                app(ChurchWorkerApprovalService::class)->reject($record, $user, $data['rejection_reason'] ?? null);
                Notification::make()->title('Inscription refusée')->warning()->send();
            });
    }

    /**
     * Marque le badge comme généré (lien public actif).
     */
    protected static function makeGenerateBadgeAction(): Action
    {
        return Action::make('generateBadge')
            ->label('Générer badge')
            ->icon('heroicon-o-qr-code')
            ->color('primary')
            ->visible(fn (ChurchWorker $record): bool => $record->status === ChurchWorker::STATUS_APPROVED && ! $record->badge_generated)
            ->action(function (ChurchWorker $record): void {
                app(ChurchWorkerApprovalService::class)->generateBadge($record);
                Notification::make()
                    ->title('Badge généré')
                    ->body(route('workers.badge.public', ['token' => $record->badge_token]))
                    ->success()
                    ->send();
            });
    }

    /**
     * Génère un lien public pour que l’ouvrier mette à jour son dossier (OTP requis).
     */
    public static function makeGenerateEditLinkAction(): Action
    {
        return Action::make('generateEditLink')
            ->label('Lien modification')
            ->icon('heroicon-o-link')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Générer un lien de modification')
            ->modalDescription('Un lien sécurisé (valable 14 jours) sera créé pour que l’ouvrier mette à jour ses informations et sa photo. La validation publique exigera un OTP e-mail.')
            ->action(function (ChurchWorker $record): void {
                $record->issueEditToken();
                $url = $record->profileEditUrl() ?? '';
                Notification::make()
                    ->title('Lien de modification généré')
                    ->body($url)
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    /**
     * Envoie le lien de modification à un ouvrier (e-mail et/ou SMS).
     */
    public static function makeNotifyEditLinkAction(): Action
    {
        return Action::make('notifyEditLink')
            ->label('Notifier lien')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->modalHeading('Envoyer le lien de modification')
            ->modalDescription('Génère un lien (14 jours) et l’envoie à cet ouvrier via le(s) canal(aux) choisi(s).')
            ->form([
                CheckboxList::make('channels')
                    ->label('Canaux')
                    ->options([
                        ChurchWorkerEditLinkNotifyService::CHANNEL_EMAIL => 'E-mail',
                        ChurchWorkerEditLinkNotifyService::CHANNEL_SMS => 'SMS',
                    ])
                    ->default([ChurchWorkerEditLinkNotifyService::CHANNEL_EMAIL])
                    ->required()
                    ->columns(2),
            ])
            ->action(function (ChurchWorker $record, array $data): void {
                $channels = array_values($data['channels'] ?? []);
                $result = app(ChurchWorkerEditLinkNotifyService::class)->notifyOne($record, $channels);

                $parts = [];
                if ($result['url']) {
                    $parts[] = $result['url'];
                }
                if ($result['email']) {
                    $parts[] = 'E-mail : '.$result['email'];
                }
                if ($result['sms']) {
                    $parts[] = 'SMS : '.$result['sms'];
                }
                if ($result['errors'] !== []) {
                    $parts = array_merge($parts, $result['errors']);
                }

                $notification = Notification::make()
                    ->title($result['ok'] ? 'Lien envoyé' : 'Envoi partiel / échoué')
                    ->body(implode("\n", $parts))
                    ->persistent();

                if ($result['ok']) {
                    $notification->success()->send();
                } else {
                    $notification->warning()->send();
                }
            });
    }

    /**
     * Envoie le lien de modification à plusieurs ouvriers sélectionnés (un par un).
     */
    public static function makeNotifyEditLinkBulkAction(): BulkAction
    {
        return BulkAction::make('notifyEditLinkBulk')
            ->label('Notifier liens modification')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->modalHeading('Notifier les ouvriers sélectionnés')
            ->modalDescription('Chaque ouvrier reçoit individuellement un lien de modification (14 jours) par e-mail et/ou SMS.')
            ->deselectRecordsAfterCompletion()
            ->form([
                CheckboxList::make('channels')
                    ->label('Canaux')
                    ->options([
                        ChurchWorkerEditLinkNotifyService::CHANNEL_EMAIL => 'E-mail',
                        ChurchWorkerEditLinkNotifyService::CHANNEL_SMS => 'SMS',
                    ])
                    ->default([ChurchWorkerEditLinkNotifyService::CHANNEL_EMAIL])
                    ->required()
                    ->columns(2),
            ])
            ->action(function (Collection $records, array $data): void {
                $channels = array_values($data['channels'] ?? []);
                $summary = app(ChurchWorkerEditLinkNotifyService::class)->notifyMany($records, $channels);

                $errors = [];
                foreach ($summary['results'] as $row) {
                    foreach ($row['errors'] as $error) {
                        $errors[] = $error;
                    }
                }

                $body = sprintf(
                    'Envoyés OK : %d · échecs / partiels : %d',
                    $summary['sent'],
                    $summary['failed'],
                );
                if ($errors !== []) {
                    $body .= "\n".implode("\n", array_slice($errors, 0, 8));
                }

                $notification = Notification::make()
                    ->title('Notification liens modification')
                    ->body($body)
                    ->persistent();

                if ($summary['failed'] === 0) {
                    $notification->success()->send();
                } else {
                    $notification->warning()->send();
                }
            });
    }

    /**
     * Ouvre la page publique du badge.
     */
    protected static function makeOpenBadgeAction(): Action
    {
        return Action::make('openBadge')
            ->label('Voir badge')
            ->icon('heroicon-o-identification')
            ->url(fn (ChurchWorker $record): string => route('workers.badge.public', ['token' => $record->badge_token]))
            ->openUrlInNewTab()
            ->visible(fn (ChurchWorker $record): bool => $record->badge_generated);
    }

    /**
     * Pages Filament (pas de page « view » : la fiche s’ouvre en modale).
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChurchWorkers::route('/'),
            'edit' => Pages\EditChurchWorker::route('/{record}/edit'),
        ];
    }

    public static function getTourStepDescription(): ?string
    {
        return 'Validez les dossiers ouvriers et générez les badges.';
    }

    /**
     * @return list<string>
     */
    public static function getTourStepFeatures(): array
    {
        return [
            'Approuver ou rejeter une inscription',
            'Notifier le lien d’édition (e-mail / SMS)',
            'Générer un lien d’édition / badge',
            'Exporter depuis le studio badges',
        ];
    }

    public static function getTourStepSort(): int
    {
        return 11;
    }
}