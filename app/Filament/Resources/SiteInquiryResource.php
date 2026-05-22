<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SiteInquiryResource\Pages;
use App\Models\SiteInquiry;
use App\Services\AppointmentConfirmationService;
use BackedEnum;
use Filament\Actions\Action;
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
use JibayMcs\Tabbed\Traits\HasTabbedActions;
use UnitEnum;

/**
 * Liste des formulaires envoyés depuis les pages prière et rendez-vous.
 */
class SiteInquiryResource extends Resource
{
    use HasTabbedActions;

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
                TextColumn::make('kind')->label('Type')->formatStateUsing(
                    fn (string $state): string => match ($state) {
                        SiteInquiry::KIND_PRAYER => 'Prière',
                        SiteInquiry::KIND_APPOINTMENT => 'Rendez-vous',
                        default => $state,
                    }
                ),
                TextColumn::make('name')->searchable()->sortable(),
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
                    ->toggleable(),
                TextColumn::make('confirmation_sms_status')
                    ->label('SMS fidèle')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, ?SiteInquiry $record): string => self::formatConfirmationSmsLabel($record))
                    ->color(fn (?string $state, ?SiteInquiry $record): string => self::confirmationSmsBadgeColor($record))
                    ->tooltip(fn (?SiteInquiry $record): ?string => self::confirmationSmsTooltip($record))
                    ->visible(fn (?SiteInquiry $record): bool => $record === null || $record->kind === SiteInquiry::KIND_APPOINTMENT)
                    ->toggleable(),
                TextColumn::make('preferred_at')->dateTime()->label('RDV')->sortable(),
                TextColumn::make('message')->label('Message')->limit(60)->tooltip(fn (SiteInquiry $r): string => $r->message),
                TextColumn::make('created_at')->label('Réception')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                ViewAction::make(),
                self::makeConfirmAppointmentAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteInquiries::route('/'),
            'view' => Pages\ViewSiteInquiry::route('/{record}'),
        ];
    }
}
