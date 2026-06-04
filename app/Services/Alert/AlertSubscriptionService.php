<?php

declare(strict_types=1);

namespace App\Services\Alert;

use App\Models\AlertSubscription;
use Illuminate\Support\Str;

/**
 * Crée ou met à jour un abonnement opt-in (live / événements).
 */
final class AlertSubscriptionService
{
    /**
     * Enregistre ou fusionne un abonnement.
     *
     * @return array{subscription: AlertSubscription, created: bool}
     */
    public function subscribe(
        ?string $email,
        ?string $phone,
        bool $notifyLive,
        bool $notifyEvents,
        string $source,
        ?string $name = null,
    ): array {
        $email = $this->normalizeEmail($email);
        $phone = $this->normalizePhone($phone);
        $name = $name !== null ? trim($name) : null;
        if ($name === '') {
            $name = null;
        }

        if ($email === null && $phone === null) {
            throw new \InvalidArgumentException('E-mail ou téléphone requis pour s’abonner.');
        }

        if (! $notifyLive && ! $notifyEvents) {
            throw new \InvalidArgumentException('Choisissez au moins une alerte (live ou événements).');
        }

        $existing = $this->findExisting($email, $phone);
        $created = $existing === null;

        if ($existing !== null) {
            $existing->update([
                'name' => $name ?? $existing->name,
                'notify_live' => $existing->notify_live || $notifyLive,
                'notify_events' => $existing->notify_events || $notifyEvents,
                'source' => $source,
            ]);

            return ['subscription' => $existing->fresh() ?? $existing, 'created' => false];
        }

        $subscription = AlertSubscription::query()->create([
            'email' => $email,
            'phone' => $phone,
            'name' => $name,
            'notify_live' => $notifyLive,
            'notify_events' => $notifyEvents,
            'source' => $source,
            'unsubscribe_token' => AlertSubscription::newUnsubscribeToken(),
        ]);

        return ['subscription' => $subscription, 'created' => true];
    }

    /**
     * Désactive toutes les alertes pour un jeton de désabonnement.
     */
    public function unsubscribeByToken(string $token): bool
    {
        $subscription = AlertSubscription::query()
            ->where('unsubscribe_token', trim($token))
            ->first();

        if ($subscription === null) {
            return false;
        }

        $subscription->update([
            'notify_live' => false,
            'notify_events' => false,
        ]);

        return true;
    }

    private function findExisting(?string $email, ?string $phone): ?AlertSubscription
    {
        if ($email !== null) {
            $byEmail = AlertSubscription::query()->where('email', $email)->first();
            if ($byEmail !== null) {
                return $byEmail;
            }
        }

        if ($phone !== null) {
            return AlertSubscription::query()->where('phone', $phone)->first();
        }

        return null;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = $email !== null ? strtolower(trim($email)) : '';

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = $phone !== null ? preg_replace('/\s+/', '', trim($phone)) ?? '' : '';

        return $phone !== '' ? $phone : null;
    }
}
