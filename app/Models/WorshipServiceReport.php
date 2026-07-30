<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Rapport de présence d'un culte saisi par l'équipe protocole.
 *
 * @property int $id
 * @property Carbon $service_date
 * @property string $service_type
 * @property int $attendees_count
 * @property string $report_text
 * @property string|null $submitted_by
 * @property string|null $phone
 */
class WorshipServiceReport extends Model
{
    public const TYPE_CULTE_DOMINICAL = 'culte_dominical';

    public const TYPE_CULTE_ENSEIGNEMENT = 'culte_enseignement';

    public const TYPE_CULTES_MATINAUX = 'cultes_matinaux';

    public const TYPE_CULTE_CELLULES = 'culte_cellules';

    public const TYPE_JEUDI_ETOKO = 'jeudi_etoko';

    public const TYPE_CULTE_MAMANS = 'culte_mamans';

    public const TYPE_AUTRE = 'autre';

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_CULTE_DOMINICAL => 'Culte Dominical',
            self::TYPE_CULTE_ENSEIGNEMENT => "Culte d'enseignement",
            self::TYPE_CULTES_MATINAUX => 'Cultes matinaux',
            self::TYPE_CULTE_CELLULES => 'Culte des cellules',
            self::TYPE_JEUDI_ETOKO => 'Jeudi Etoko',
            self::TYPE_CULTE_MAMANS => 'Culte des Mamans',
            self::TYPE_AUTRE => 'Autre',
        ];
    }

    protected $fillable = [
        'service_date',
        'service_type',
        'attendees_count',
        'report_text',
        'submitted_by',
        'phone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'attendees_count' => 'integer',
        ];
    }

    /**
     * Libellé lisible du type de culte.
     */
    public function serviceTypeLabel(): string
    {
        return self::typeOptions()[$this->service_type] ?? $this->service_type;
    }
}
