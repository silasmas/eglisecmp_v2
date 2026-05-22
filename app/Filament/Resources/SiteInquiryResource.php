<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SiteInquiryResource\Pages;
use App\Models\SiteInquiry;
use App\Services\AppointmentConfirmationService;
use App\Services\PrayerRequestNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Liste des formulaires envoyés depuis les pages prière et rendez-vous.
 */
class SiteInquiryResource extends Resource
{
    use HasTabbedActions;

    public const LIST_TAB_ALL = 'all';

    public const LIST_TAB_PRAYER = 'prayer';

    public const LIST_TAB_APPOINTMENT = 'appointment';

    protected static ?string $model = SiteInquiry::class;

    protected static ?string $navigationLabel = 'Demandes (prière / RDV)';

    protected static ?string $modelLabel = 'Demande';

    protected static ?string $pluralModelLabel = 'Demandes';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Réception')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('kind')->label('Type')->columnSpan(4),
                        TextEntry::make('minister.fullname')
                            ->label('Pasteur')
                            ->visible(fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_APPOINTMENT)
                            ->formatStateUsing(fn ($state): string => MinisterResource::normalizeLegacyValue($state) ?? '—')
                            ->columnSpan(4),
                        TextEntry::make('bureau.name')
                            ->label('Bureau')
                            ->visible(fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_APPOINTMENT)
                            ->placeholder('—')
                            ->columnSpan(4),
                        TextEntry::make('appointment_status')
                            ->label('Statut RDV')
                            ->visible(fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_APPOINTMENT)
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                SiteInquiry::STATUS_PENDING => 'En attente',
                                SiteInquiry::STATUS_CONFIRMED => 'Confirmé',
                                SiteInquiry::STATUS_DECLINED => 'Refusé',
                                default => $state,
                            })
                            ->columnSpan(4),
                        TextEntry::make('confirmation_sms_status')
                            ->label('SMS fidèle')
                            ->visible(fn (?SiteInquiry $record): bool => $record !== null
                                && $record->kind === SiteInquiry::KIND_APPOINTMENT
                                && $record->appointment_status === SiteInquiry::STATUS_CONFIRMED)
                            ->formatStateUsing(fn (?string $state, ?SiteInquiry $record): string => self::formatConfirmationSmsLabel($record))
                            ->badge()
                            ->color(fn (?string $state, ?SiteInquiry $record): string => self::confirmationSmsBadgeColor($record))
                            ->columnSpan(4),
                        TextEntry::make('confirmation_sms_sent_at')
                            ->label('SMS envoyé le')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn (?SiteInquiry $record): bool => $record !== null
                                && $record->kind === SiteInquiry::KIND_APPOINTMENT
                                && $record->confirmation_sms_sent_at !== null)
                            ->columnSpan(4),
                        TextEntry::make('confirmation_sms_response')
                            ->label('Retour passerelle SMS')
                            ->visible(fn (?SiteInquiry $record): bool => $record !== null
                                && $record->kind === SiteInquiry::KIND_APPOINTMENT
                                && filled($record->confirmation_sms_response))
                            ->columnSpanFull(),
                        TextEntry::make('name')->label('Nom')->columnSpan(8),
                        TextEntry::make('is_anonymous')
                            ->label('Anonymat')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                            ->visible(fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_PRAYER)
                            ->columnSpan(4),
                        TextEntry::make('country')
                            ->label('Pays')
                            ->visible(fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_PRAYER)
                            ->columnSpan(4),
                        TextEntry::make('prayer_team_notification_status')
                            ->label('Équipe de prière')
                            ->visible(fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_PRAYER)
                            ->formatStateUsing(fn (?string $state, SiteInquiry $record): string => $record->prayerTeamNotificationLabel() ?? '—')
                            ->badge()
                            ->color(fn (?string $state, SiteInquiry $record): string => $record->prayerTeamNotificationBadgeColor())
                            ->columnSpan(4),
                        TextEntry::make('prayer_team_notified_at')
                            ->label('Équipe notifiée le')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn (?SiteInquiry $record): bool => $record !== null
                                && $record->kind === SiteInquiry::KIND_PRAYER
                                && $record->prayer_team_notified_at !== null)
                            ->columnSpan(4),
                        TextEntry::make('prayer_team_notification_response')
                            ->label('Retour notification prière')
                            ->visible(fn (?SiteInquiry $record): bool => $record !== null
                                && $record->kind === SiteInquiry::KIND_PRAYER
                                && filled($record->prayer_team_notification_response))
                            ->columnSpanFull(),
                        TextEntry::make('email')->columnSpan(6),
                        TextEntry::make('phone')->columnSpan(6),
                        TextEntry::make('preferred_at')->dateTime()->label('Date souhaitée')->columnSpan(6),
                        TextEntry::make('created_at')->dateTime()->label('Envoyée le')->columnSpan(6),
                        TextEntry::make('message')
                            ->label('Message')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Aucune édition côté admin : données issues du site uniquement.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kind')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => match ($state) {
                            SiteInquiry::KIND_PRAYER => 'Prière',
                            SiteInquiry::KIND_APPOINTMENT => 'Rendez-vous',
                            default => $state,
                        },
                    )
                    ->color(
                        fn (string $state): string => match ($state) {
                            SiteInquiry::KIND_PRAYER => 'info',
                            SiteInquiry::KIND_APPOINTMENT => 'warning',
                            default => 'gray',
                        },
                    )
                    ->visible(fn (?object $livewire): bool => self::listTabShowsKindColumn($livewire))
                    ->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('country')->label('Pays')
                    ->visible(
                        fn (?SiteInquiry $record, ?object $livewire): bool => self::listTabShowsPrayerColumns($livewire, $record),
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_anonymous')
                    ->label('Anonyme')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : '—')
                    ->visible(
                        fn (?SiteInquiry $record, ?object $livewire): bool => self::listTabShowsPrayerColumns($livewire, $record),
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')->label('Téléphone')->toggleable(),
                TextColumn::make('appointment_status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        SiteInquiry::STATUS_CONFIRMED => 'Confirmé',
                        SiteInquiry::STATUS_DECLINED => 'Refusé',
                        default => 'En attente',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        SiteInquiry::STATUS_CONFIRMED => 'success',
                        SiteInquiry::STATUS_DECLINED => 'danger',
                        default => 'warning',
                    })
                    ->visible(
                        fn (?SiteInquiry $record, ?object $livewire): bool => self::listTabShowsAppointmentColumns($livewire, $record),
                    )
                    ->toggleable(),
                TextColumn::make('confirmation_sms_status')
                    ->label('SMS fidèle')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, ?SiteInquiry $record): string => self::formatConfirmationSmsLabel($record))
                    ->color(fn (?string $state, ?SiteInquiry $record): string => self::confirmationSmsBadgeColor($record))
                    ->tooltip(fn (?SiteInquiry $record): ?string => self::confirmationSmsTooltip($record))
                    ->visible(
                        fn (?SiteInquiry $record, ?object $livewire): bool => self::listTabShowsAppointmentColumns($livewire, $record),
                    )
                    ->toggleable(),
                TextColumn::make('prayer_team_notification_status')
                    ->label('Équipe prière')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, SiteInquiry $record): string => $record->prayerTeamNotificationLabel() ?? 'En attente')
                    ->color(fn (?string $state, SiteInquiry $record): string => $record->prayerTeamNotificationBadgeColor())
                    ->tooltip(fn (SiteInquiry $record): ?string => filled($record->prayer_team_notification_response)
                        ? (string) $record->prayer_team_notification_response
                        : null)
                    ->visible(
                        fn (?SiteInquiry $record, ?object $livewire): bool => self::listTabShowsPrayerColumns($livewire, $record),
                    )
                    ->toggleable(),
                TextColumn::make('preferred_at')
                    ->dateTime()
                    ->label('RDV')
                    ->visible(
                        fn (?SiteInquiry $record, ?object $livewire): bool => self::listTabShowsAppointmentColumns($livewire, $record),
                    )
                    ->sortable(),
                TextColumn::make('message')->label('Message')->limit(60)->tooltip(fn (SiteInquiry $r): string => $r->message),
                TextColumn::make('created_at')->label('Réception')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                self::makeConfirmAppointmentAction(),
                self::makeNotifyPrayerTeamAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    self::makeBulkConfirmAppointmentAction(),
                    self::makeBulkNotifyPrayerTeamAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Action Filament : confirmer le RDV et envoyer le SMS au fidèle.
     */
    public static function makeConfirmAppointmentAction(): Action
    {
        return Action::make('confirmAppointment')
            ->label(fn (SiteInquiry $record): string => $record->canRetryConfirmationSms()
                ? 'Renvoyer SMS'
                : 'Confirmer')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (SiteInquiry $record): bool => $record->canBeConfirmed())
            ->requiresConfirmation()
            ->modalHeading(fn (SiteInquiry $record): string => $record->canRetryConfirmationSms()
                ? 'Renvoyer le SMS de confirmation'
                : 'Confirmer le rendez-vous')
            ->modalDescription('Le rendez-vous ne sera confirmé que si le SMS part avec succès. En cas d’échec, le bouton reste disponible pour réessayer.')
            ->action(function (SiteInquiry $record, AppointmentConfirmationService $confirmationService): void {
                $result = $confirmationService->confirm($record);
                $sms = $result['sms'];

                if ($result['confirmed'] && $sms->isNotified()) {
                    Notification::make()
                        ->title('Rendez-vous confirmé')
                        ->body($sms->adminMessage())
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('SMS non envoyé')
                    ->body($sms->adminMessage().' Le rendez-vous reste en attente.')
                    ->danger()
                    ->send();
            });
    }

    /**
     * Action Filament : notifier l’équipe de prière par e-mail pour une requête.
     */
    public static function makeNotifyPrayerTeamAction(): Action
    {
        return Action::make('notifyPrayerTeam')
            ->label('Notifier l’équipe de prière')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->visible(fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_PRAYER)
            ->requiresConfirmation()
            ->modalHeading('Notifier l’équipe de prière')
            ->modalDescription('Un courriel sera envoyé à tous les comptes intercession (et aux adresses PRAYER_TEAM_EMAILS le cas échéant).')
            ->action(function (SiteInquiry $record, PrayerRequestNotificationService $notificationService): void {
                $result = $notificationService->notifyAndRecord($record);

                if ($result->hasSuccess()) {
                    Notification::make()
                        ->title('Équipe de prière notifiée')
                        ->body($result->adminSummary())
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Notification non envoyée')
                    ->body($result->adminSummary())
                    ->danger()
                    ->send();
            });
    }

    /**
     * Action groupée : confirmer les RDV sélectionnés et envoyer le SMS aux fidèles.
     */
    public static function makeBulkConfirmAppointmentAction(): BulkAction
    {
        return BulkAction::make('bulkConfirmAppointments')
            ->label('Confirmer et envoyer SMS')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->visible(
                fn (?object $livewire, Collection $records): bool => self::bulkActionVisibleForAppointment($livewire, $records),
            )
            ->requiresConfirmation()
            ->modalHeading('Confirmer les rendez-vous sélectionnés')
            ->modalDescription(
                fn (?object $livewire): string => self::resolveListTab($livewire) === self::LIST_TAB_ALL
                    ? 'Seuls les rendez-vous parmi la sélection seront confirmés et recevront un SMS.'
                    : 'Chaque rendez-vous éligible sera confirmé et un SMS partira vers le fidèle.',
            )
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, AppointmentConfirmationService $confirmationService, ?object $livewire): void {
                $appointments = $records->filter(
                    fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_APPOINTMENT,
                );

                if ($appointments->isEmpty()) {
                    Notification::make()
                        ->title('Aucun rendez-vous sélectionné')
                        ->body('Sélectionnez des demandes de type « Rendez-vous ».')
                        ->warning()
                        ->send();

                    return;
                }

                $summary = $confirmationService->confirmMany($appointments);

                $body = sprintf(
                    '%d SMS envoyé(s), %d échec(s).',
                    $summary['confirmed'],
                    $summary['failed'],
                );

                $ignoredCount = $records->count() - $appointments->count();

                if ($ignoredCount > 0 && SiteInquiryResource::resolveListTab($livewire) === SiteInquiryResource::LIST_TAB_ALL) {
                    $body .= sprintf(' %d demande(s) ignorée(s) (non RDV).', $ignoredCount);
                }

                if ($summary['errors'] !== []) {
                    $body .= ' '.implode(' · ', array_slice($summary['errors'], 0, 2));
                }

                Notification::make()
                    ->title('Traitement des rendez-vous terminé')
                    ->body($body)
                    ->color($summary['confirmed'] > 0 ? 'success' : 'danger')
                    ->send();
            });
    }

    /**
     * Action groupée : notifier l’équipe de prière pour les requêtes sélectionnées.
     */
    public static function makeBulkNotifyPrayerTeamAction(): BulkAction
    {
        return BulkAction::make('bulkNotifyPrayerTeam')
            ->label('Notifier l’équipe de prière')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->visible(
                fn (?object $livewire, Collection $records): bool => self::bulkActionVisibleForPrayer($livewire, $records),
            )
            ->requiresConfirmation()
            ->modalHeading('Notifier l’équipe de prière')
            ->modalDescription(
                fn (?object $livewire): string => self::resolveListTab($livewire) === self::LIST_TAB_ALL
                    ? 'Seules les requêtes de prière parmi la sélection recevront un courriel à l’équipe d’intercession.'
                    : 'Un courriel sera envoyé pour chaque requête de prière sélectionnée.',
            )
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, PrayerRequestNotificationService $notificationService, ?object $livewire): void {
                $prayers = $records->filter(
                    fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_PRAYER,
                );

                if ($prayers->isEmpty()) {
                    Notification::make()
                        ->title('Aucune requête de prière sélectionnée')
                        ->body('Sélectionnez des demandes de type « Prière ».')
                        ->warning()
                        ->send();

                    return;
                }

                $sent = 0;
                $failed = 0;

                foreach ($prayers as $inquiry) {
                    $result = $notificationService->notifyAndRecord($inquiry);

                    if ($result->hasSuccess()) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }

                $body = sprintf('%d requête(s) notifiée(s), %d échec(s).', $sent, $failed);

                $ignoredCount = $records->count() - $prayers->count();

                if ($ignoredCount > 0 && SiteInquiryResource::resolveListTab($livewire) === SiteInquiryResource::LIST_TAB_ALL) {
                    $body .= sprintf(' %d demande(s) ignorée(s) (non prière).', $ignoredCount);
                }

                Notification::make()
                    ->title('Notifications prière terminées')
                    ->body($body)
                    ->color($sent > 0 ? 'success' : 'danger')
                    ->send();
            });
    }

    /**
     * Libellé badge SMS dans la liste admin.
     */
    public static function formatConfirmationSmsLabel(?SiteInquiry $record): string
    {
        if ($record === null) {
            return '—';
        }

        if ($record->confirmationSmsLabel() !== null) {
            return $record->confirmationSmsLabel();
        }

        if ($record->appointment_status === SiteInquiry::STATUS_CONFIRMED) {
            return 'Non envoyé';
        }

        return '—';
    }

    /**
     * Couleur Filament du badge SMS.
     */
    public static function confirmationSmsBadgeColor(?SiteInquiry $record): string
    {
        if ($record === null) {
            return 'gray';
        }

        return match ($record->confirmation_sms_status) {
            SiteInquiry::SMS_STATUS_SENT => 'success',
            SiteInquiry::SMS_STATUS_SIMULATED => 'info',
            SiteInquiry::SMS_STATUS_NO_PHONE => 'gray',
            SiteInquiry::SMS_STATUS_FAILED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Infobulle détaillée sur le statut SMS.
     */
    public static function confirmationSmsTooltip(?SiteInquiry $record): ?string
    {
        if ($record === null || $record->confirmation_sms_status === null) {
            return null;
        }

        if ($record->confirmation_sms_sent_at !== null) {
            $sentAt = $record->confirmation_sms_sent_at->timezone((string) config('app.timezone'))->format('d/m/Y H:i');
            $response = trim((string) ($record->confirmation_sms_response ?? ''));

            if ($response !== '') {
                return 'Envoyé le '.$sentAt.' — '.$response;
            }

            return 'Envoyé le '.$sentAt;
        }

        return filled($record->confirmation_sms_response)
            ? (string) $record->confirmation_sms_response
            : null;
    }

    /**
     * @param  object|null  $livewire  Page Filament ListSiteInquiries (propriété activeTab).
     * @return string Identifiant d’onglet actif (all, prayer, appointment).
     */
    public static function resolveListTab(?object $livewire): string
    {
        if ($livewire === null || ! property_exists($livewire, 'activeTab') || blank($livewire->activeTab)) {
            return self::LIST_TAB_ALL;
        }

        return (string) $livewire->activeTab;
    }

    /**
     * @param  object|null  $livewire  Page Filament ListSiteInquiries.
     * @return bool Afficher la colonne Type (onglet « Toutes » uniquement).
     */
    public static function listTabShowsKindColumn(?object $livewire): bool
    {
        return self::resolveListTab($livewire) === self::LIST_TAB_ALL;
    }

    /**
     * @param  object|null  $livewire  Page Filament ListSiteInquiries.
     * @param  SiteInquiry|null  $record  Ligne courante (null lors du rendu d’en-tête).
     * @return bool Afficher les colonnes propres aux requêtes de prière.
     */
    public static function listTabShowsPrayerColumns(?object $livewire, ?SiteInquiry $record): bool
    {
        return match (self::resolveListTab($livewire)) {
            self::LIST_TAB_PRAYER => true,
            self::LIST_TAB_APPOINTMENT => false,
            default => $record === null || $record->kind === SiteInquiry::KIND_PRAYER,
        };
    }

    /**
     * @param  object|null  $livewire  Page Filament ListSiteInquiries.
     * @param  SiteInquiry|null  $record  Ligne courante (null lors du rendu d’en-tête).
     * @return bool Afficher les colonnes propres aux rendez-vous.
     */
    public static function listTabShowsAppointmentColumns(?object $livewire, ?SiteInquiry $record): bool
    {
        return match (self::resolveListTab($livewire)) {
            self::LIST_TAB_APPOINTMENT => true,
            self::LIST_TAB_PRAYER => false,
            default => $record === null || $record->kind === SiteInquiry::KIND_APPOINTMENT,
        };
    }

    /**
     * @param  object|null  $livewire  Page Filament ListSiteInquiries.
     * @param  Collection<int, SiteInquiry>  $records  Lignes sélectionnées pour l’action groupée.
     * @return bool Afficher l’action groupée de confirmation RDV / SMS.
     */
    public static function bulkActionVisibleForAppointment(?object $livewire, Collection $records): bool
    {
        $tab = self::resolveListTab($livewire);

        if ($tab === self::LIST_TAB_PRAYER) {
            return false;
        }

        if ($tab === self::LIST_TAB_APPOINTMENT) {
            return true;
        }

        return $records->contains(
            fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_APPOINTMENT,
        );
    }

    /**
     * @param  object|null  $livewire  Page Filament ListSiteInquiries.
     * @param  Collection<int, SiteInquiry>  $records  Lignes sélectionnées pour l’action groupée.
     * @return bool Afficher l’action groupée de notification équipe de prière.
     */
    public static function bulkActionVisibleForPrayer(?object $livewire, Collection $records): bool
    {
        $tab = self::resolveListTab($livewire);

        if ($tab === self::LIST_TAB_APPOINTMENT) {
            return false;
        }

        if ($tab === self::LIST_TAB_PRAYER) {
            return true;
        }

        return $records->contains(
            fn (SiteInquiry $record): bool => $record->kind === SiteInquiry::KIND_PRAYER,
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteInquiries::route('/'),
            'view' => Pages\ViewSiteInquiry::route('/{record}'),
        ];
    }
}
