<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Département ministériel CMP (ouvriers / badges).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $color
 * @property int|null $manager_user_id
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property bool $is_active
 * @property int $sort_order
 */
class ChurchDepartment extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'manager_user_id',
        'contact_phone',
        'contact_email',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChurchDepartment $department): void {
            if (blank($department->slug)) {
                $department->slug = Str::slug($department->name);
            }
        });
    }

    /**
     * Responsable du département (validation des inscriptions).
     *
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    /**
     * Ouvriers rattachés.
     *
     * @return HasMany<ChurchWorker, $this>
     */
    public function workers(): HasMany
    {
        return $this->hasMany(ChurchWorker::class, 'department_id');
    }

    /**
     * Responsables du département (contacts + comptes éventuels).
     *
     * @return HasMany<ChurchDepartmentManager, $this>
     */
    public function managers(): HasMany
    {
        return $this->hasMany(ChurchDepartmentManager::class, 'department_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Responsable principal (flag is_primary, sinon le premier).
     */
    public function primaryManager(): ?ChurchDepartmentManager
    {
        $this->loadMissing('managers');

        return $this->managers->firstWhere('is_primary', true) ?? $this->managers->first();
    }
}
