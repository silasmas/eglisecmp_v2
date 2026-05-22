<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Resultat d'un envoi SMS (succes, echec ou numero absent).
 */
final readonly class SmsSendResult
{
    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_NO_PHONE = 'no_phone';

    public const STATUS_SIMULATED = 'simulated';

    public function __construct(
        public string $status,
        public bool $success,
        public ?string $response = null,
        public ?string $error = null,
    ) {}

    /**
     * Indique si le destinataire a ete informe (envoi reel ou simulation locale).
     */
    public function isNotified(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_SIMULATED], true);
    }

    /**
     * Libelle court pour l'interface admin.
     */
    public function adminLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'Informe par SMS',
            self::STATUS_SIMULATED => 'Informe (simulation)',
            self::STATUS_NO_PHONE => 'Sans telephone',
            default => 'Echec SMS',
        };
    }

    /**
     * Message detaille pour une notification Filament.
     */
    public function adminMessage(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'Le fidèle a été informé par SMS.',
            self::STATUS_SIMULATED => 'SMS journalisé en local (mode log).',
            self::STATUS_NO_PHONE => 'Aucun numéro de téléphone : le fidèle n\'a pas pu être prévenu.',
            default => $this->error ?? 'La passerelle SMS a refusé ou n\'a pas confirmé l\'envoi.',
        };
    }
}
