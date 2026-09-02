<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use Illuminate\Support\Str;

/**
 * Crée ou met à jour un dossier ouvrier à partir d’un contact responsable.
 *
 * Un responsable CMP est d’abord un ouvrier (dossier church_workers) ;
 * le compte User « ouvrier » n’est créé qu’à la validation admin.
 */
final class ChurchWorkerFromContactService
{
    /** Date sentinelle : profil à compléter via le lien d’édition. */
    public const PLACEHOLDER_BIRTH_DATE = '1900-01-01';

    public const PLACEHOLDER_COMMUNE = 'Gombe';

    public const PLACEHOLDER_QUARTIER = 'À compléter';

    public const PLACEHOLDER_AVENUE = 'À compléter';

    /**
     * Upsert un ChurchWorker pour un département à partir du nom / tél / e-mail.
     *
     * @return array{worker: ChurchWorker|null, created: bool, skipped_reason: string|null}
     */
    public function upsert(
        ChurchDepartment $department,
        string $fullName,
        ?string $phone,
        ?string $email,
        bool $isPrimary = false,
    ): array {
        $fullName = trim($fullName);
        $phone = filled($phone) ? trim((string) $phone) : null;
        $email = filled($email) ? mb_strtolower(trim((string) $email)) : null;

        if ($fullName === '') {
            return ['worker' => null, 'created' => false, 'skipped_reason' => 'Nom manquant'];
        }

        if ($phone === null) {
            return ['worker' => null, 'created' => false, 'skipped_reason' => 'Téléphone manquant pour '.$fullName];
        }

        [$firstName, $lastName] = $this->splitFullName($fullName);
        $role = $isPrimary ? 'Responsable principal' : 'Responsable';

        $existing = $this->findExisting($department->id, $phone, $email);

        if ($existing !== null) {
            $existing->fill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email ?? $existing->email,
                'department_role' => filled($existing->department_role) ? $existing->department_role : $role,
            ]);
            $existing->save();

            return ['worker' => $existing->refresh(), 'created' => false, 'skipped_reason' => null];
        }

        $worker = ChurchWorker::query()->create([
            'department_id' => $department->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => ChurchWorker::GENDER_MALE,
            'birth_date' => self::PLACEHOLDER_BIRTH_DATE,
            'phone' => $phone,
            'email' => $email,
            'city' => 'Kinshasa',
            'commune' => self::PLACEHOLDER_COMMUNE,
            'quartier' => self::PLACEHOLDER_QUARTIER,
            'avenue' => self::PLACEHOLDER_AVENUE,
            'address_reference' => 'Créé depuis import responsables — dossier à compléter',
            'department_role' => $role,
            'status' => ChurchWorker::STATUS_PENDING,
        ]);

        return ['worker' => $worker, 'created' => true, 'skipped_reason' => null];
    }

    /**
     * Recherche un ouvrier existant du même département (e-mail puis téléphone).
     */
    public function findExisting(int $departmentId, string $phone, ?string $email): ?ChurchWorker
    {
        $base = ChurchWorker::query()->where('department_id', $departmentId);

        if ($email !== null && $email !== '') {
            $byEmail = (clone $base)->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
            if ($byEmail !== null) {
                return $byEmail;
            }
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $candidates = (clone $base)->where(function ($q) use ($phone, $digits): void {
            $q->where('phone', $phone);
            if ($digits !== '') {
                $q->orWhere('phone', $digits)
                    ->orWhere('phone', 'like', '%'.$digits);
            }
        })->get();

        foreach ($candidates as $candidate) {
            $candidateDigits = preg_replace('/\D+/', '', (string) $candidate->phone) ?? '';
            if ($candidateDigits !== '' && $digits !== '' && str_ends_with($candidateDigits, substr($digits, -9))) {
                return $candidate;
            }
            if ($candidate->phone === $phone) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Découpe « Prénom Nom » (dernier mot = nom de famille).
     *
     * @return array{0: string, 1: string}
     */
    public function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $p): bool => $p !== ''));

        if ($parts === []) {
            return ['Responsable', 'CMP'];
        }

        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }

        $lastName = array_pop($parts);

        return [implode(' ', $parts), (string) $lastName];
    }

    /**
     * Indique si le dossier est encore un profil « placeholder » d’import.
     */
    public function isIncompletePlaceholder(ChurchWorker $worker): bool
    {
        $birth = $worker->birth_date?->format('Y-m-d');

        return $birth === self::PLACEHOLDER_BIRTH_DATE
            || $worker->quartier === self::PLACEHOLDER_QUARTIER
            || Str::contains((string) $worker->address_reference, 'import responsables');
    }
}
