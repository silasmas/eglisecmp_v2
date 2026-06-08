<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\SiteSchedulerRunner;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Page admin : exécution manuelle du scheduler, cron HTTP et état de santé.
 */
class SiteSchedulerPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Tâches planifiées';

    protected static ?string $title = 'Tâches planifiées (cron)';

    protected static string|UnitEnum|null $navigationGroup = 'Système';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.site-scheduler';

    public bool $httpCronEnabled = false;

    /**
     * Charge l’état du cron HTTP depuis le cache.
     */
    public function mount(): void
    {
        $this->httpCronEnabled = SiteSchedulerRunner::isHttpCronEnabled();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'status' => SiteSchedulerRunner::status(),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('runScheduler')
                ->label('Exécuter le scheduler')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Lancer schedule:run')
                ->modalDescription('Exécute immédiatement les tâches dues (YouTube, live, alertes).')
                ->action(function (): void {
                    $result = SiteSchedulerRunner::run('filament');

                    if ($result['success']) {
                        Notification::make()
                            ->title('Scheduler exécuté')
                            ->body('Les tâches planifiées ont été vérifiées.')
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Échec du scheduler')
                        ->body($result['error'] ?? 'Consultez les logs Laravel.')
                        ->danger()
                        ->send();
                }),
        ];
    }

    /**
     * Active ou désactive le cron HTTP (URL /deploy/scheduler).
     */
    public function toggleHttpCron(): void
    {
        $this->httpCronEnabled = ! $this->httpCronEnabled;
        SiteSchedulerRunner::setHttpCronEnabled($this->httpCronEnabled);

        Notification::make()
            ->title($this->httpCronEnabled ? 'Cron HTTP activé' : 'Cron HTTP désactivé')
            ->body(
                $this->httpCronEnabled
                    ? 'Configurez un service externe pour appeler l’URL toutes les minutes.'
                    : 'L’URL /deploy/scheduler ne lancera plus le scheduler.'
            )
            ->success()
            ->send();
    }

    /**
     * Teste une commande planifiée individuelle.
     */
    public function testCommand(string $command): void
    {
        $result = SiteSchedulerRunner::runCommand($command);

        if ($result['success']) {
            Notification::make()
                ->title('Commande OK : '.$command)
                ->body($result['output'] !== '' ? $result['output'] : 'Terminé sans sortie.')
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Échec : '.$command)
            ->body($result['error'] ?? $result['output'] ?? 'Erreur inconnue.')
            ->danger()
            ->send();
    }
}
