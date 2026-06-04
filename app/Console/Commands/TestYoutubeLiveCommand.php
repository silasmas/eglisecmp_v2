<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\YoutubeLiveStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Vérifie la configuration YouTube Live (API + chaîne).
 */
class TestYoutubeLiveCommand extends Command
{
    protected $signature = 'youtube:test-live';

    protected $description = 'Teste la détection du live YouTube (config .env)';

    public function handle(YoutubeLiveStatusService $live): int
    {
        $channelId = (string) config('site_public.youtube_channel_id', '');
        $apiKey = (string) config('services.youtube.api_key', '');

        if ($channelId === '') {
            $this->error('YOUTUBE_CHANNEL_ID est vide.');

            return self::FAILURE;
        }

        if ($apiKey === '') {
            $this->error('YOUTUBE_API_KEY est vide.');

            return self::FAILURE;
        }

        $this->line('Chaîne : '.$channelId);
        $this->line('Clé API : '.Str::mask($apiKey, '*', 4, -4));

        $current = $live->current();

        if ($current === null) {
            $this->warn('Aucun live actif détecté (normal si la chaîne ne diffuse pas).');

            return self::SUCCESS;
        }

        $this->info('Live détecté !');
        $this->table(['Titre', 'ID', 'URL'], [[
            $current['title'],
            $current['videoId'],
            $current['watchUrl'],
        ]]);

        return self::SUCCESS;
    }
}
