<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChurchDepartment;
use App\Models\ChurchDepartmentManager;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Import des départements + responsables depuis Excel (format CMP RESPO ou modèle plat).
 */
final class ChurchDepartmentManagersImportService
{
    public function __construct(
        private readonly ChurchWorkerFromContactService $workerFromContact,
    ) {}

    /**
     * Importe un fichier Excel de responsables.
     *
     * @return array{success: bool, message: string, created_departments: int, updated_departments: int, managers: int, users: int, workers_created: int, workers_updated: int, errors: list<string>}
     */
    public function importFromPath(string $absolutePath, bool $replaceManagers = true): array
    {
        $errors = [];
        $createdDepartments = 0;
        $updatedDepartments = 0;
        $managersCount = 0;
        $usersCount = 0;
        $workersCreated = 0;
        $workersUpdated = 0;

        try {
            $spreadsheet = IOFactory::load($absolutePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (Throwable $e) {
            return $this->emptyResult('Impossible de lire le fichier Excel : '.$e->getMessage());
        }

        $groups = $this->parseRows($rows);
        if ($groups === []) {
            return $this->emptyResult('Aucun département / responsable détecté. Vérifiez le format du fichier.');
        }

        $sortBase = 1;

        DB::beginTransaction();
        try {
            foreach ($groups as $group) {
                $deptName = $group['department'];
                $slug = Str::slug($deptName);
                if ($slug === '') {
                    $errors[] = 'Nom de département invalide : '.$deptName;

                    continue;
                }

                $department = ChurchDepartment::query()->where('slug', $slug)->first();
                if ($department === null) {
                    $department = ChurchDepartment::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($deptName)])->first();
                }

                if ($department === null) {
                    $department = ChurchDepartment::query()->create([
                        'name' => $deptName,
                        'slug' => $slug,
                        'color' => '#7b1d3e',
                        'is_active' => true,
                        'sort_order' => $sortBase++,
                    ]);
                    $createdDepartments++;
                } else {
                    $department->update([
                        'name' => $deptName,
                        'is_active' => true,
                    ]);
                    $updatedDepartments++;
                }

                if ($replaceManagers) {
                    $department->managers()->delete();
                }

                $primaryUserId = null;
                $primaryPhone = null;
                $primaryEmail = null;
                $order = 0;

                foreach ($group['managers'] as $index => $managerRow) {
                    $fullName = $managerRow['name'];
                    $phone = $this->cleanPhone($managerRow['phone']);
                    $email = $this->cleanEmail($managerRow['email']);
                    $isPrimary = $index === 0;

                    $userId = null;
                    if ($email !== null) {
                        $user = User::query()->where('email', $email)->first();
                        if ($user === null) {
                            $user = User::query()->create([
                                'name' => $fullName,
                                'email' => $email,
                                'password' => Hash::make(Str::password(12, symbols: false)),
                            ]);
                            $usersCount++;
                        } else {
                            $user->update(['name' => $fullName]);
                        }
                        $userId = $user->id;
                    }

                    ChurchDepartmentManager::query()->create([
                        'department_id' => $department->id,
                        'full_name' => $fullName,
                        'phone' => $phone,
                        'email' => $email,
                        'user_id' => $userId,
                        'is_primary' => $isPrimary,
                        'sort_order' => $order++,
                    ]);
                    $managersCount++;

                    $workerResult = $this->workerFromContact->upsert(
                        $department,
                        $fullName,
                        $phone,
                        $email,
                        $isPrimary,
                    );
                    if ($workerResult['worker'] === null) {
                        if ($workerResult['skipped_reason'] !== null) {
                            $errors[] = $workerResult['skipped_reason'];
                        }
                    } elseif ($workerResult['created']) {
                        $workersCreated++;
                    } else {
                        $workersUpdated++;
                    }

                    if ($isPrimary) {
                        $primaryUserId = $userId;
                        $primaryPhone = $phone;
                        $primaryEmail = $email;
                    }
                }

                $department->update([
                    'manager_user_id' => $primaryUserId ?? $department->manager_user_id,
                    'contact_phone' => $primaryPhone ?? $department->contact_phone,
                    'contact_email' => $primaryEmail ?? $department->contact_email,
                ]);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            return $this->emptyResult('Erreur pendant l’import : '.$e->getMessage(), [$e->getMessage()]);
        }

        return [
            'success' => $errors === [],
            'message' => sprintf(
                'Départements créés : %d · mis à jour : %d · responsables : %d · dossiers ouvriers créés : %d · mis à jour : %d · comptes admin : %d',
                $createdDepartments,
                $updatedDepartments,
                $managersCount,
                $workersCreated,
                $workersUpdated,
                $usersCount,
            ),
            'created_departments' => $createdDepartments,
            'updated_departments' => $updatedDepartments,
            'managers' => $managersCount,
            'users' => $usersCount,
            'workers_created' => $workersCreated,
            'workers_updated' => $workersUpdated,
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<string>  $errors
     * @return array{success: bool, message: string, created_departments: int, updated_departments: int, managers: int, users: int, workers_created: int, workers_updated: int, errors: list<string>}
     */
    private function emptyResult(string $message, array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'created_departments' => 0,
            'updated_departments' => 0,
            'managers' => 0,
            'users' => 0,
            'workers_created' => 0,
            'workers_updated' => 0,
            'errors' => $errors,
        ];
    }

    /**
     * Détecte et parse le format CMP (A=n°, B=département, C=nom, D=tél, E=email)
     * ou un format plat (département, responsable, telephone, email).
     *
     * @param  list<list<mixed>>  $rows
     * @return list<array{department: string, managers: list<array{name: string, phone: string, email: string}>}>
     */
    public function parseRows(array $rows): array
    {
        $headerIndexes = $this->detectFlatHeader($rows);
        if ($headerIndexes !== null) {
            return $this->parseFlatFormat($rows, $headerIndexes);
        }

        return $this->parseCmpRespoFormat($rows);
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @return array{department: int, name: int, phone: int, email: int}|null
     */
    private function detectFlatHeader(array $rows): ?array
    {
        foreach (array_slice($rows, 0, 15) as $row) {
            $normalized = [];
            foreach ($row as $i => $cell) {
                $key = Str::of((string) $cell)->lower()->ascii()->replace([' ', '-', '_'], '')->toString();
                $normalized[$key] = (int) $i;
            }

            $deptKey = $this->firstKey($normalized, ['departement', 'department', 'nomdepartement']);
            $nameKey = $this->firstKey($normalized, ['responsable', 'manager', 'nom', 'fullname_name', 'responsablenom']);
            $phoneKey = $this->firstKey($normalized, ['telephone', 'phone', 'numero', 'numeros', 'tel']);
            $emailKey = $this->firstKey($normalized, ['email', 'mail', 'courriel']);

            if ($deptKey !== null && $nameKey !== null) {
                return [
                    'department' => $normalized[$deptKey],
                    'name' => $normalized[$nameKey],
                    'phone' => $phoneKey !== null ? $normalized[$phoneKey] : -1,
                    'email' => $emailKey !== null ? $normalized[$emailKey] : -1,
                ];
            }
        }

        return null;
    }

    /**
     * @param  array<string, int>  $normalized
     * @param  list<string>  $candidates
     */
    private function firstKey(array $normalized, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $normalized)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<list<mixed>>  $rows
     * @param  array{department: int, name: int, phone: int, email: int}  $indexes
     * @return list<array{department: string, managers: list<array{name: string, phone: string, email: string}>}>
     */
    private function parseFlatFormat(array $rows, array $indexes): array
    {
        $grouped = [];
        $started = false;

        foreach ($rows as $row) {
            $dept = trim((string) ($row[$indexes['department']] ?? ''));
            $name = trim((string) ($row[$indexes['name']] ?? ''));
            if (! $started) {
                $probe = Str::of($dept.$name)->lower()->ascii()->toString();
                if (str_contains($probe, 'depart') || str_contains($probe, 'respons')) {
                    $started = true;

                    continue;
                }
            }

            if ($dept === '' || $name === '') {
                continue;
            }

            $phone = $indexes['phone'] >= 0 ? trim((string) ($row[$indexes['phone']] ?? '')) : '';
            $email = $indexes['email'] >= 0 ? trim((string) ($row[$indexes['email']] ?? '')) : '';
            $key = mb_strtoupper($dept);
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'department' => $dept,
                    'managers' => [],
                ];
            }
            $grouped[$key]['managers'][] = [
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
            ];
        }

        return array_values($grouped);
    }

    /**
     * Format CREA DATABASE RESPO CMP.xlsx.
     *
     * @param  list<list<mixed>>  $rows
     * @return list<array{department: string, managers: list<array{name: string, phone: string, email: string}>}>
     */
    private function parseCmpRespoFormat(array $rows): array
    {
        $grouped = [];
        $currentDept = null;

        foreach ($rows as $row) {
            $colA = trim((string) ($row[0] ?? ''));
            $colB = trim((string) ($row[1] ?? ''));
            $colC = trim((string) ($row[2] ?? ''));
            $colD = trim((string) ($row[3] ?? ''));
            $colE = trim((string) ($row[4] ?? ''));

            $upperB = mb_strtoupper($colB);
            $upperC = mb_strtoupper($colC);

            if ($upperB === 'DÉPARTEMENT' || $upperB === 'DEPARTEMENT' || str_contains(mb_strtoupper($colA), 'DATABASE')) {
                continue;
            }
            if ($upperC === 'RESPONSABLES' || $upperC === 'RESPONSABLE') {
                continue;
            }

            if ($colB !== '' && ! ctype_digit($colB)) {
                $currentDept = $colB;
                if (! isset($grouped[$currentDept])) {
                    $grouped[$currentDept] = [
                        'department' => $currentDept,
                        'managers' => [],
                    ];
                }
            }

            // Cas où le nom de département est en colonne A (sans numéro) — rare.
            if ($currentDept === null && $colA !== '' && ! ctype_digit($colA) && ! str_contains(mb_strtoupper($colA), 'DATABASE')) {
                $currentDept = $colA;
                $grouped[$currentDept] = [
                    'department' => $currentDept,
                    'managers' => [],
                ];
            }

            if ($currentDept === null || $colC === '') {
                continue;
            }

            $grouped[$currentDept]['managers'][] = [
                'name' => $colC,
                'phone' => $colD,
                'email' => $colE,
            ];
        }

        return array_values(array_filter(
            $grouped,
            fn (array $g): bool => $g['managers'] !== [],
        ));
    }

    private function cleanPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        // Retire caractères invisibles / annotations entre parenthèses.
        $phone = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $phone) ?? $phone;
        if (preg_match('/\(([^)]*)\)/', $phone, $m) === 1 && preg_match('/\d/', $m[1]) !== 1) {
            $phone = trim(str_replace($m[0], '', $phone));
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 8) {
            return null;
        }

        return $phone;
    }

    private function cleanEmail(?string $email): ?string
    {
        $email = trim((string) $email);
        if ($email === '' || strcasecmp($email, 'N/A') === 0 || strcasecmp($email, 'NA') === 0) {
            return null;
        }

        $email = str_replace(' ', '', $email);
        $email = str_ireplace(['.con', '.conm'], '.com', $email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return mb_strtolower($email);
    }
}
