<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enfant rattaché à une demande de présentation (lien parent ↔ enfant).
 *
 * @property int $id
 * @property int $child_presentation_id
 * @property string $full_name
 * @property string $gender male|female
 * @property int $age_years
 * @property int $age_months
 */
class PresentedChild extends Model
{
    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    protected $fillable = [
        'child_presentation_id',
        'full_name',
        'gender',
        'age_years',
        'age_months',
    ];

    /**
     * Libellé français du sexe.
     *
     * @param  string  $gender  male|female
     */
    public static function genderLabel(string $gender): string
    {
        return match ($gender) {
            self::GENDER_FEMALE => 'Fille',
            default => 'Garçon',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age_years' => 'integer',
            'age_months' => 'integer',
        ];
    }

    /**
     * Demande de présentation parente.
     *
     * @return BelongsTo<ChildPresentation, $this>
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ChildPresentation::class, 'child_presentation_id');
    }

    /**
     * Âge total en mois pour les calculs ECODIM.
     */
    public function ageInMonths(): int
    {
        return ($this->age_years * 12) + $this->age_months;
    }
}
