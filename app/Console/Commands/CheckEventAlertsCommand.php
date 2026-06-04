<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Alert\EventAlertNotificationService;
use Illuminate\Console\Command;

/**
 * Vérifie les événements (en cours, à la une, rappel 24 h) et notifie les abonnés opt-in.
 */
class CheckEventAlertsCommand extends Command
{
    protected $signature = 'events:check-alerts';

    protected $description = 'Envoie les alertes événements aux abonnés (opt-in)';

    public function handle(EventAlertNotificationService $service): int
    {
        $result = $service->checkAndNotify();

        $this->info(sprintf(
            'Alertes événements : %d envoi(s) en cours, %d à la une, %d rappels à venir.',
            $result['ongoing'],
            $result['spotlight'],
            $result['upcoming'],
        ));

        return self::SUCCESS;
    }
}
