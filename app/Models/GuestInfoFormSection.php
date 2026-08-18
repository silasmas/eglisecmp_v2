<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rubrique d’un formulaire de renseignement.
 *
 * @property int $id
 * @property int $form_id
 * @property string $title
 * @property string|null $description
 * @property int $sort_order
 * @property array<int, int>|null $department_ids
 */
class GuestInfoFormSection extends Model
{
    protected $fillable = [
        'form_id',
        'title',
        'description',
        'sort_order',
        'department_ids',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'department_ids' => 'array',
        ];
    }

    /**
     * Formulaire parent.
     *
     * @return BelongsTo<GuestInfoForm, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(GuestInfoForm::class, 'form_id');
    }

    /**
     * Questions de la rubrique.
     *
     * @return HasMany<GuestInfoFormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(GuestInfoFormField::class, 'section_id')->orderBy('sort_order');
    }
}
