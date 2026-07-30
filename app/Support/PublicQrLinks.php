<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pages publiques accessibles par QR code / lien direct.
 */
final class PublicQrLinks
{
    /**
     * @return list<array{key: string, label: string, description: string, path: string}>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'raccourcis',
                'label' => 'Raccourcis (landing QR)',
                'description' => 'Page d’accueil des raccourcis scannables',
                'path' => '/raccourcis',
            ],
            [
                'key' => 'presentation-enfants',
                'label' => 'Présentation des enfants',
                'description' => 'Formulaire parents — 2e et 4e dimanche',
                'path' => '/presentation-enfants',
            ],
            [
                'key' => 'stats-culte',
                'label' => 'Stats culte (protocole)',
                'description' => 'Saisie des statistiques de participation',
                'path' => '/protocole/stats-culte',
            ],
            [
                'key' => 'ouvriers-inscription',
                'label' => 'Inscription ouvrier',
                'description' => 'Dossier ouvrier + badge (QR / lien uniquement)',
                'path' => '/ouvriers/inscription',
            ],
        ];
    }

    /**
     * URL absolue d'une page QR.
     */
    public static function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }
}
