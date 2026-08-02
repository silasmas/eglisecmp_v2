<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Minister;
use App\Models\User;

/**
 * Droits d'accès à la réception pastorale selon le compte connecté.
 */
final class PastoralAccess
{
    /**
     * Pasteur lié au compte utilisateur (s'il existe).
     */
    public static function linkedMinister(?User $user): ?Minister
    {
        if ($user === null) {
            return null;
        }

        return Minister::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Indique si l'utilisateur est pasteur titulaire.
     */
    public static function isTitular(?User $user): bool
    {
        $minister = self::linkedMinister($user);

        return $minister !== null && (bool) $minister->is_titular;
    }

    /**
     * Seul le pasteur titulaire peut orienter un fidèle vers un autre pasteur
     * (après accusée de réception uniquement — contrôlé côté UI).
     */
    public static function canOrient(?User $user): bool
    {
        return self::isTitular($user);
    }

    /**
     * Admin : peut rediriger un dossier non encore reçu.
     * Le titulaire n’utilise pas cette action (il oriente après réception).
     */
    public static function canAdminRedirect(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (self::isTitular($user)) {
            return false;
        }

        return $user->can('ViewAny:SiteInquiry') || $user->can('Update:SiteInquiry');
    }

    /**
     * Pasteur assigné (ou admin) peut accuser réception du fidèle.
     */
    public static function canMarkReceived(?User $user, int $assignedMinisterId): bool
    {
        if ($user === null) {
            return false;
        }

        if (self::canAdminRedirect($user) || self::canViewAllAppointments($user)) {
            return true;
        }

        $linked = self::linkedMinister($user);

        return $linked !== null && (int) $linked->id === $assignedMinisterId;
    }

    /**
     * Indique si l'utilisateur voit tous les rendez-vous (admin ou titulaire).
     */
    public static function canViewAllAppointments(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return self::isTitular($user);
    }

    /**
     * ID du pasteur auquel limiter la liste (null = pas de filtre).
     */
    public static function scopedMinisterId(?User $user): ?int
    {
        if (self::canViewAllAppointments($user)) {
            return null;
        }

        return self::linkedMinister($user)?->id;
    }
}
