<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Question / champ d’une rubrique de formulaire.
 *
 * @property int $id
 * @property int $section_id
 * @property string $key
 * @property string $label
 * @property string $type
 * @property array<string, mixed>|null $options
 * @property array<int, int>|null $department_ids
 * @property bool $required
 * @property int $sort_order
 * @property string|null $help_text
 */
class GuestInfoFormField extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_EMAIL = 'email';

    public const TYPE_PHONE = 'phone';

    public const TYPE_YES_NO = 'yes_no';

    public const TYPE_CHECKBOX_GROUP = 'checkbox_group';

    public const TYPE_FOOD_GRID = 'food_grid';

    public const TYPE_REPEATER_NAMES = 'repeater_names';

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    protected $fillable = [
        'section_id',
        'key',
        'label',
        'type',
        'options',
        'department_ids',
        'required',
        'sort_order',
        'help_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'department_ids' => 'array',
            'required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GuestInfoFormField $field): void {
            if (blank($field->key)) {
                $field->key = \Illuminate\Support\Str::slug((string) $field->label, '_').'_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(3));
            }
        });
    }

    /**
     * Libellés des types de champs.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_TEXT => 'Texte',
            self::TYPE_TEXTAREA => 'Texte long',
            self::TYPE_EMAIL => 'E-mail',
            self::TYPE_PHONE => 'Téléphone',
            self::TYPE_YES_NO => 'Oui / Non',
            self::TYPE_CHECKBOX_GROUP => 'Cases à cocher',
            self::TYPE_SINGLE_CHOICE => 'Choix unique',
            self::TYPE_FOOD_GRID => 'Grille nourriture',
            self::TYPE_REPEATER_NAMES => 'Liste de noms',
        ];
    }

    /**
     * IDs départements effectifs (champ ou rubrique).
     *
     * @return list<int>
     */
    public function effectiveDepartmentIds(): array
    {
        $fromField = $this->department_ids ?? [];
        if ($fromField !== []) {
            return array_values(array_map('intval', $fromField));
        }

        $sectionIds = $this->section?->department_ids ?? [];

        return array_values(array_map('intval', $sectionIds));
    }

    /**
     * Rubrique parente.
     *
     * @return BelongsTo<GuestInfoFormSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(GuestInfoFormSection::class, 'section_id');
    }
}
