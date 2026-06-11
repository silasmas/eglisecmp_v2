<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Youtube\YoutubeLiveNotificationService;
use App\Services\YoutubeLiveStatusService;
use Illuminate\Console\Command;

/**
 * Détecte un live YouTube et notifie les contacts du site (si activé dans .env).
 */
class CheckYoutubeLiveCommand extends Command
{
    protected $signature = 'youtube:check-live';

    protected $description = 'Vérifie si un live YouTube a démarré et envoie email/SMS aux contacts configurés';

    public function handle(YoutubeLiveNotificationService $notifier, YoutubeLiveStatusService $liveStatus): int
    {
        $snapshot = $liveStatus->snapshot(true);
        $result = $notifier->checkAndNotify();

        if ($result['notified']) {
            $this->info('Notifications live envoyées pour '.$result['videoId']
                .' ('.$result['emails'].' email(s), '.$result['sms'].' SMS).');

            return self::SUCCESS;
        }

        if ($result['videoId'] !== null) {
            $this->line('Live détecté ('.$result['videoId'].') — déjà notifié ou inchangé.');
        } else {
            $this->line('Aucun live YouTube en cours.');
            if ($snapshot['isLive'] === false) {
                $this->line('Vérifiez YOUTUBE_CHANNEL_ID et YOUTUBE_API_KEY dans .env si un live est actif sur YouTube.');
            }
        }

        return self::SUCCESS;
    }
}
