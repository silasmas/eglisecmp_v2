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
     * Indique si l'utilisateur voit tous les rendez-vous (titulaire ou super_admin uniquement).
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
     * ID du pasteur auquel limiter la liste.
     * null = vue globale (titulaire / super_admin).
     * 0 = aucun accès liste (pas de pasteur lié et pas de vue globale).
     */
    public static function scopedMinisterId(?User $user): ?int
    {
        if (self::canViewAllAppointments($user)) {
            return null;
        }

        $linkedId = self::linkedMinister($user)?->id;

        return $linkedId !== null ? (int) $linkedId : 0;
    }

    /**
     * Peut consulter un dossier (pasteur assigné, titulaire ou super_admin uniquement).
     * Après clôture, seul le titulaire / super_admin peut rouvrir pour voir le dossier.
     */
    public static function canAccessDossier(?User $user, \App\Models\SiteInquiry $record): bool
    {
        if ($user === null || $record->kind !== \App\Models\SiteInquiry::KIND_APPOINTMENT) {
            return false;
        }

        if (self::isDossierClosed($record) && ! self::canViewAllAppointments($user)) {
            return false;
        }

        if (self::canViewAllAppointments($user)) {
            return true;
        }

        $linked = self::linkedMinister($user);

        return $linked !== null && (int) $record->minister_id === (int) $linked->id;
    }

    /**
     * Indique si le dossier est clôturé.
     */
    public static function isDossierClosed(\App\Models\SiteInquiry $record): bool
    {
        return ($record->dossier_status ?? null) === \App\Models\SiteInquiry::DOSSIER_CLOSED;
    }

    /**
     * Édition active : interdit si clos/suspendu (sauf réouverture titulaire).
     */
    public static function canEditDossier(?User $user, \App\Models\SiteInquiry $record): bool
    {
        if (! self::canAccessDossier($user, $record)) {
            return false;
        }

        if (self::isDossierClosed($record)) {
            return false;
        }

        if (($record->dossier_status ?? null) === \App\Models\SiteInquiry::DOSSIER_SUSPENDED
            && ! self::canViewAllAppointments($user)) {
            return false;
        }

        return true;
    }

    /**
     * Seul le titulaire (ou super_admin) peut réouvrir un dossier clos.
     */
    public static function canReopen(?User $user): bool
    {
        return self::canViewAllAppointments($user);
    }
}
