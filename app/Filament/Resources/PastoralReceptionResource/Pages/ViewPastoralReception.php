<?php

declare(strict_types=1);

namespace App\Filament\Resources\PastoralReceptionResource\Pages;

use App\Filament\Resources\PastoralReceptionResource;
use App\Models\SiteInquiry;
use App\Models\User;
use App\Services\PastoralSessionService;
use App\Support\PastoralAccess;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Consultation d'un dossier RDV pastoral avec chrono de séance.
 */
class ViewPastoralReception extends ViewRecord
{
    protected static string $resource = PastoralReceptionResource::class;

    /**
     * Rafraîchit le chrono / alertes temps.
     */
    public function getPollingInterval(): ?string
    {
        /** @var SiteInquiry $record */
        $record = $this->getRecord();

        if ($record->received_at === null || PastoralAccess::isDossierClosed($record)) {
            return null;
        }

        return '5s';
    }

    /**
     * @return array<Action|EditAction>
     */
    protected function getHeaderActions(): array
    {
        /** @var SiteInquiry $record */
        $record = $this->getRecord();
        $user = auth()->user() instanceof User ? auth()->user() : null;
        $session = app(PastoralSessionService::class);
        $chrono = $session->chronoState($record);

        return [
            Action::make('chronoInfo')
                ->label($chrono['label'])
                ->icon('heroicon-o-clock')
                ->color(match (true) {
                    $chrono['overdue'] => 'danger',
                    $chrono['warning'] => 'warning',
                    default => 'info',
                })
                ->disabled()
                ->visible($chrono['started']
                    && ! PastoralAccess::isDossierClosed($record)
                    && ($record->dossier_status ?? '') !== SiteInquiry::DOSSIER_FOLLOW_UP),
            Action::make('adjustDuration')
                ->label('Gérer le temps')
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(PastoralAccess::canEditDossier($user, $record) && $record->received_at !== null)
                ->form([
                    TextInput::make('session_duration_minutes')
                        ->label('Durée (minutes)')
                        ->numeric()
                        ->required()
                        ->minValue(5)
                        ->maxValue(240)
                        ->default((int) ($record->session_duration_minutes ?: PastoralSessionService::DEFAULT_DURATION_MINUTES)),
                ])
                ->action(function (array $data) use ($session): void {
                    $session->updateDuration($this->getRecord(), (int) $data['session_duration_minutes']);
                    Notification::make()->title('Durée mise à jour')->success()->send();
                }),
            EditAction::make()
                ->label('Compléter le dossier')
                ->visible(PastoralReceptionResource::canEdit($record)),
        ];
    }

    /**
     * Alerte temps sous le titre.
     */
    public function getSubheading(): string|Htmlable|null
    {
        /** @var SiteInquiry $record */
        $record = $this->getRecord();

        if ($record->received_at === null || PastoralAccess::isDossierClosed($record)) {
            return null;
        }

        $state = app(PastoralSessionService::class)->chronoState($record);

        if ($state['overdue']) {
            return '⚠ '.$state['label'].' — concluez ou prolongez la durée.';
        }

        if ($state['warning']) {
            return '⏱ Fin de séance proche : '.$state['label'];
        }

        return $state['started'] ? $state['label'] : null;
    }
}
