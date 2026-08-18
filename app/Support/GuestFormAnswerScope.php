<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\ChurchDepartment;
use App\Models\GuestInfoFormField;
use App\Models\GuestInfoSubmission;
use App\Models\User;

/**
 * Filtre les réponses du formulaire d’accueil selon les droits (admin vs département).
 */
final class GuestFormAnswerScope
{
    /**
     * Indique si l’utilisateur voit toutes les réponses (tous départements).
     */
    public static function canViewAll(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->can('ViewAny:GuestInfoSubmission')
            && $user->can('view_all_guest_form_answers');
    }

    /**
     * IDs des départements gérés par l’utilisateur (responsable).
     *
     * @return list<int>
     */
    public static function managedDepartmentIds(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return ChurchDepartment::query()
            ->where('manager_user_id', $user->id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Peut consulter une soumission (admin ou responsable d’au moins un département).
     */
    public static function canViewSubmission(?User $user, GuestInfoSubmission $submission): bool
    {
        if (self::canViewAll($user)) {
            return true;
        }

        return self::managedDepartmentIds($user) !== [];
    }

    /**
     * Filtre le payload pour ne garder que les champs des départements autorisés.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<int>|null  $departmentIds  null = tout garder
     * @param  int|null  $formId  Limite aux champs du formulaire
     * @return array<string, mixed>
     */
    public static function filterPayload(array $payload, ?array $departmentIds, ?int $formId = null): array
    {
        if ($departmentIds === null) {
            return $payload;
        }

        $query = GuestInfoFormField::query()->with('section');
        if ($formId !== null) {
            $query->whereHas('section', fn ($q) => $q->where('form_id', $formId));
        }

        $allowedKeys = [];
        foreach ($query->get() as $field) {
            $effective = $field->effectiveDepartmentIds();
            if ($effective === []) {
                continue;
            }
            if (array_intersect($effective, $departmentIds) !== []) {
                $allowedKeys[] = $field->key;
            }
        }

        $filtered = [];
        foreach ($payload as $key => $value) {
            if (in_array($key, $allowedKeys, true)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Payload visible pour un utilisateur Filament connecté.
     *
     * @return array<string, mixed>
     */
    public static function visiblePayloadForUser(?User $user, GuestInfoSubmission $submission): array
    {
        $payload = $submission->payload ?? [];

        if (self::canViewAll($user)) {
            return $payload;
        }

        return self::filterPayload(
            $payload,
            self::managedDepartmentIds($user),
            (int) $submission->form_id,
        );
    }

    /**
     * Filtre le payload pour un département précis (portail e-mail).
     *
     * @return array<string, mixed>
     */
    public static function visiblePayloadForDepartment(GuestInfoSubmission $submission, int $departmentId): array
    {
        return self::filterPayload(
            $submission->payload ?? [],
            [$departmentId],
            (int) $submission->form_id,
        );
    }
}
