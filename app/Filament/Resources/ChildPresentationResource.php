<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Concerns\ProvidesAdminTourStep;
use App\Filament\Resources\ChildPresentationResource\Pages;
use App\Models\ChildPresentation;
use App\Models\PresentedChild;
use App\Services\ChildPresentationConfirmationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

/**
 * Gestion admin des demandes de présentation d'enfants (validation + SMS).
 */
class ChildPresentationResource extends Resource
{
    use ProvidesAdminTourStep;
    protected static ?string $model = ChildPresentation::class;

    protected static ?string $navigationLabel = 'Présentation enfants';

    protected static ?string $modelLabel = 'Présentation';

    protected static ?string $pluralModelLabel = 'Présentations d\'enfants';

    protected static ?string $recordTitleAttribute = 'parent_names';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 25;

    /**
     * Fiche détail lecture seule.
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->schema([
                Section::make('Demande')
                    ->columnSpanFull()
                    ->columns(12)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Statut')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                ChildPresentation::STATUS_CONFIRMED => 'Confirmée',
                                ChildPresentation::STATUS_DECLINED => 'Refusée',
                                default => 'En attente',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                ChildPresentation::STATUS_CONFIRMED => 'success',
                                ChildPresentation::STATUS_DECLINED => 'danger',
                                default => 'warning',
                            })
                            ->columnSpan(4),
                        TextEntry::make('presentation_date')
                            ->label('Date de présentation')
                            ->date('d/m/Y')
                            ->columnSpan(4),
                        TextEntry::make('children_count')
                            ->label('Nombre d\'enfants')
                            ->columnSpan(4),
                        TextEntry::make('parent_names')
                            ->label('Parents')
                            ->columnSpan(6),
                        TextEntry::make('phone')
                            ->label('Téléphone')
                            ->columnSpan(3),
                        TextEntry::make('phone_verified')
                            ->label('Tél. vérifié')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                            ->columnSpan(3),
                        TextEntry::make('confirmation_sms_status')
                            ->label('SMS parent')
                            ->formatStateUsing(fn (?string $state, ChildPresentation $record): string => self::formatSmsLabel($record))
                            ->badge()
                            ->columnSpan(4),
                        TextEntry::make('confirmation_sms_sent_at')
                            ->label('SMS envoyé le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—')
                            ->columnSpan(4),
                        TextEntry::make('confirmed_at')
                            ->label('Confirmée le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—')
                            ->columnSpan(4),
                        TextEntry::make('birth_certificate_path')
                            ->label('Acte(s) de naissance')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Voir le fichier' : '—')
                            ->url(fn (ChildPresentation $record): ?string => self::publicFileUrl($record->birth_certificate_path))
                            ->openUrlInNewTab()
                            ->columnSpan(6),
                        TextEntry::make('parent_id_document_path')
                            ->label('Pièce d\'identité parent')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Voir le fichier' : '—')
                            ->url(fn (ChildPresentation $record): ?string => self::publicFileUrl($record->parent_id_document_path))
                            ->openUrlInNewTab()
                            ->columnSpan(6),
                        TextEntry::make('created_at')
                            ->label('Reçue le')
                            ->dateTime('d/m/Y H:i')
                            ->columnSpan(6),
                        TextEntry::make('confirmation_sms_response')
                            ->label('Retour SMS')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Enfants')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('children')
                            ->label('')
                            ->schema([
                                TextEntry::make('full_name')->label('Nom complet'),
                                TextEntry::make('gender')
                                    ->label('Sexe')
                                    ->formatStateUsing(fn (?string $state): string => PresentedChild::genderLabel((string) $state)),
                                TextEntry::make('age_years')->label('Âge (ans)'),
                                TextEntry::make('age_months')->label('Mois'),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    /**
     * Pas de création / édition manuelle : données issues du site.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('children'))
            ->columns([
                TextColumn::make('parent_names')
                    ->label('Parents')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('children_count')
                    ->label('Enfants')
                    ->sortable(),
                TextColumn::make('children_names')
                    ->label('Noms enfants')
                    ->state(fn (ChildPresentation $record): string => $record->children
                        ->pluck('full_name')
                        ->implode(', '))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('presentation_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ChildPresentation::STATUS_CONFIRMED => 'Confirmée',
                        ChildPresentation::STATUS_DECLINED => 'Refusée',
                        default => 'En attente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        ChildPresentation::STATUS_CONFIRMED => 'success',
                        ChildPresentation::STATUS_DECLINED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('confirmation_sms_status')
                    ->label('SMS')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, ChildPresentation $record): string => self::formatSmsLabel($record))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Réception')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        ChildPresentation::STATUS_PENDING => 'En attente',
                        ChildPresentation::STATUS_CONFIRMED => 'Confirmée',
                        ChildPresentation::STATUS_DECLINED => 'Refusée',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                self::makeConfirmAction(),
                self::makeDeclineAction(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    self::makeBulkConfirmAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Confirme la présentation et envoie le SMS au parent.
     */
    public static function makeConfirmAction(): Action
    {
        return Action::make('confirmPresentation')
            ->label(fn (ChildPresentation $record): string => $record->isParentNotifiedBySms()
                ? 'Renvoyer SMS'
                : 'Confirmer')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (ChildPresentation $record): bool => $record->canBeConfirmed())
            ->requiresConfirmation()
            ->modalHeading('Confirmer la présentation')
            ->modalDescription('Un SMS sera envoyé au parent pour confirmer la présentation et demander d\'être présent au début du culte.')
            ->action(function (ChildPresentation $record, ChildPresentationConfirmationService $service): void {
                $result = $service->confirm($record);
                $sms = $result['sms'];

                if ($result['confirmed'] && $sms->isNotified()) {
                    Notification::make()
                        ->title('Présentation confirmée')
                        ->body($sms->adminMessage())
                        ->success()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('SMS non envoyé')
                    ->body($sms->adminMessage().' La demande reste en attente.')
                    ->danger()
                    ->send();
            });
    }

    /**
     * Refuse une demande en attente.
     */
    public static function makeDeclineAction(): Action
    {
        return Action::make('declinePresentation')
            ->label('Refuser')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (ChildPresentation $record): bool => $record->status === ChildPresentation::STATUS_PENDING)
            ->requiresConfirmation()
            ->action(function (ChildPresentation $record, ChildPresentationConfirmationService $service): void {
                $service->decline($record);

                Notification::make()
                    ->title('Présentation refusée')
                    ->success()
                    ->send();
            });
    }

    /**
     * Confirmation groupée avec SMS.
     */
    public static function makeBulkConfirmAction(): BulkAction
    {
        return BulkAction::make('confirmPresentations')
            ->label('Confirmer + SMS')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (Collection $records, ChildPresentationConfirmationService $service): void {
                $ok = 0;
                $fail = 0;

                foreach ($records as $record) {
                    if (! $record instanceof ChildPresentation || ! $record->canBeConfirmed()) {
                        $fail++;
                        continue;
                    }

                    try {
                        $result = $service->confirm($record);
                        if ($result['confirmed']) {
                            $ok++;
                        } else {
                            $fail++;
                        }
                    } catch (\Throwable) {
                        $fail++;
                    }
                }

                Notification::make()
                    ->title('Confirmations terminées')
                    ->body("Confirmées : {$ok}. Échecs : {$fail}.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Libellé badge SMS.
     */
    public static function formatSmsLabel(ChildPresentation $record): string
    {
        return match ($record->confirmation_sms_status) {
            ChildPresentation::SMS_STATUS_SENT => 'Envoyé',
            ChildPresentation::SMS_STATUS_SIMULATED => 'Simulé',
            ChildPresentation::SMS_STATUS_FAILED => 'Échec',
            ChildPresentation::SMS_STATUS_NO_PHONE => 'Sans tél.',
            default => '—',
        };
    }

    /**
     * URL publique d'un fichier stocké sur le disque public.
     */
    public static function publicFileUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChildPresentations::route('/'),
            'view' => Pages\ViewChildPresentation::route('/{record}'),
        ];
    }

    public static function getTourStepDescription(): ?string
    {
        return 'Traitez les demandes de présentation d’enfants.';
    }

    /**
     * @return list<string>
     */
    public static function getTourStepFeatures(): array
    {
        return [
            'Confirmer une présentation',
            'Refuser une demande',
            'Suivre les statistiques',
        ];
    }

    public static function getTourStepSort(): int
    {
        return 35;
    }
}
