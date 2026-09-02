<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Responsable rattaché à un département ministériel CMP.
 *
 * @property int $id
 * @property int $department_id
 * @property string $full_name
 * @property string|null $phone
 * @property string|null $email
 * @property int|null $user_id
 * @property bool $is_primary
 * @property int $sort_order
 */
class ChurchDepartmentManager extends Model
{
    protected $fillable = [
        'department_id',
        'full_name',
        'phone',
        'email',
        'user_id',
        'is_primary',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Département concerné.
     *
     * @return BelongsTo<ChurchDepartment, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(ChurchDepartment::class, 'department_id');
    }

    /**
     * Compte utilisateur lié (si créé / trouvé via e-mail).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
