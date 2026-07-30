<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Évite la page blanche locale liée à un serveur Vite (hot) mort ou inaccessible.
 *
 * Par défaut en local, on privilégie `public/build` (stable).
 * Pour le HMR Vite : définir `VITE_USE_DEV=true` dans `.env` puis `npm run dev`.
 */
final class ViteHotFallback
{
    /**
     * Désactive le mode hot si le build existe, sauf si VITE_USE_DEV=true.
     */
    public static function ensureUsableAssets(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $hotFile = public_path('hot');
        $manifest = public_path('build/manifest.json');

        if (! is_file($hotFile)) {
            return;
        }

        // Mode développement Vite explicite : on garde le hot file.
        if (filter_var(env('VITE_USE_DEV', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        // Build disponible → servir les assets compilés (pas de dépendance à :5173).
        if (is_file($manifest)) {
            @unlink($hotFile);
        }
    }
}
