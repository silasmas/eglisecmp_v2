<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Résultat agrégé d’un envoi e-mail vers l’équipe d’intercession.
 */
final class PrayerTeamNotificationResult
{
    public int $sent = 0;

    public int $failed = 0;

    /** @var list<string> */
    public array $errors = [];

    /**
     * @param  list<string>  $errors  Messages d’erreur par destinataire.
     */
    public function __construct(
        int $sent = 0,
        int $failed = 0,
        array $errors = [],
    ) {
        $this->sent = $sent;
        $this->failed = $failed;
        $this->errors = $errors;
    }

    /**
     * Indique si au moins un courriel est parti sans échec bloquant.
     */
    public function hasSuccess(): bool
    {
        return $this->sent > 0;
    }

    /**
     * @return string Résumé lisible pour l’admin Filament.
     */
    public function adminSummary(): string
    {
        if ($this->sent === 0 && $this->failed === 0) {
            return 'Aucun destinataire intercession avec une adresse e-mail valide.';
        }

        $parts = ["{$this->sent} courriel(s) envoyé(s)"];

        if ($this->failed > 0) {
            $parts[] = "{$this->failed} échec(s)";
        }

        if ($this->errors !== []) {
            $parts[] = implode(' · ', array_slice($this->errors, 0, 3));
        }

        return implode(' — ', $parts);
    }
}
