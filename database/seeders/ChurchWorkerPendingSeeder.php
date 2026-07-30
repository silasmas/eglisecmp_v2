<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ChurchDepartment;
use App\Models\ChurchWorker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 10 ouvriers en attente de validation (démo / tests admin).
 *
 * Idempotent : recrée uniquement les e-mails seedés manquants.
 */
class ChurchWorkerPendingSeeder extends Seeder
{
    /**
     * Insère 10 dossiers ouvriers status=pending.
     *
     * @return void
     */
    public function run(): void
    {
        if (ChurchDepartment::query()->count() === 0) {
            $this->call(ChurchDepartmentSeeder::class);
        }

        $departments = ChurchDepartment::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($departments->isEmpty()) {
            $this->command?->warn('Aucun département : seeder ouvriers ignoré.');

            return;
        }

        $rows = [
            ['last_name' => 'Kabongo', 'first_name' => 'Grace', 'gender' => 'female', 'commune' => 'Gombe', 'role' => 'Accueil porte', 'dept' => 'accueil'],
            ['last_name' => 'Ilunga', 'first_name' => 'Patrick', 'gender' => 'male', 'commune' => 'Lingwala', 'role' => 'Intercesseur', 'dept' => 'intercession'],
            ['last_name' => 'Mwamba', 'first_name' => 'Sarah', 'gender' => 'female', 'commune' => 'Kintambo', 'role' => 'Chef d’équipe', 'dept' => 'securite'],
            ['last_name' => 'Tshilombo', 'first_name' => 'David', 'gender' => 'male', 'commune' => 'Limete', 'role' => 'Cuisine', 'dept' => 'restauration'],
            ['last_name' => 'Kalonji', 'first_name' => 'Esther', 'gender' => 'female', 'commune' => 'Ngaliema', 'role' => 'Sono', 'dept' => 'technique'],
            ['last_name' => 'Ngoie', 'first_name' => 'Joseph', 'gender' => 'male', 'commune' => 'Masina', 'role' => 'Nettoyage', 'dept' => 'hygiene'],
            ['last_name' => 'Mbayo', 'first_name' => 'Ruth', 'gender' => 'female', 'commune' => 'Lemba', 'role' => 'Créatif', 'dept' => 'crea'],
            ['last_name' => 'Kasongo', 'first_name' => 'Pierre', 'gender' => 'male', 'commune' => 'Bandalungwa', 'role' => 'Secouriste', 'dept' => 'sante'],
            ['last_name' => 'Lukusa', 'first_name' => 'Deborah', 'gender' => 'female', 'commune' => 'Kalamu', 'role' => 'Protocole VIP', 'dept' => 'protocole'],
            ['last_name' => 'Mutombo', 'first_name' => 'Samuel', 'gender' => 'male', 'commune' => 'Ngiri-Ngiri', 'role' => 'Choriste', 'dept' => 'chorale'],
        ];

        foreach ($rows as $index => $row) {
            $email = 'ouvrier.seed.'.($index + 1).'@cmp.local';
            $department = $departments->firstWhere('slug', $row['dept']) ?? $departments[$index % $departments->count()];

            ChurchWorker::query()->updateOrCreate(
                ['email' => $email],
                [
                    'department_id' => $department->id,
                    'last_name' => $row['last_name'],
                    'first_name' => $row['first_name'],
                    'gender' => $row['gender'] === 'female'
                        ? ChurchWorker::GENDER_FEMALE
                        : ChurchWorker::GENDER_MALE,
                    'birth_date' => now()->subYears(22 + $index)->subMonths($index)->toDateString(),
                    'phone' => '2438'.str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT),
                    'email_verified_at' => now(),
                    'city' => 'Kinshasa',
                    'commune' => $row['commune'],
                    'quartier' => 'Quartier '.$row['commune'],
                    'avenue' => 'Avenue de l’Église',
                    'address_reference' => 'Près du marché',
                    'studies' => 'Licence',
                    'education_level' => 'Universitaire',
                    'profession' => 'Étudiant(e)',
                    'skills' => 'Service, accueil, travail d’équipe',
                    'department_role' => $row['role'],
                    'department_joined_at' => now()->subMonths(3 + $index)->toDateString(),
                    'status' => ChurchWorker::STATUS_PENDING,
                    'rejection_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'badge_token' => (string) Str::uuid(),
                    'badge_generated' => false,
                    'badge_generated_at' => null,
                    'user_id' => null,
                ]
            );
        }
    }
}
