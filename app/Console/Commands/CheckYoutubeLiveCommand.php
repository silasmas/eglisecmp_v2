<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Youtube\YoutubeLiveNotificationService;
use App\Services\YoutubeLiveStatusService;
use Illuminate\Console\Command;

/**
 * Rafraîchit le cache live YouTube et notifie les abonnés si un nouveau live démarre.
 */
class CheckYoutubeLiveCommand extends Command
{
    protected $signature = 'youtube:check-live';

    protected $description = 'Rafraîchit le statut live YouTube (cache) et envoie les alertes si configuré';

    public function handle(YoutubeLiveStatusService $liveStatus, YoutubeLiveNotificationService $notifier): int
    {
        $snapshot = $liveStatus->snapshot(true);

        if ($snapshot['isLive'] && $snapshot['live'] !== null) {
            $this->info('Live actif : '.$snapshot['live']['title'].' ('.$snapshot['live']['videoId'].')');
        } else {
            $this->line('Aucun live YouTube en cours.');
        }

        $result = $notifier->checkAndNotify();

        if ($result['notified']) {
            $this->info('Notifications envoyées ('.$result['emails'].' email(s), '.$result['sms'].' SMS).');
        }

        return self::SUCCESS;
    }
}
