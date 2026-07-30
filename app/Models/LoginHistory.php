<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Entrée d'historique de connexion au dashboard.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $email
 * @property string|null $name
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $guard
 * @property string $status
 * @property Carbon $logged_in_at
 */
class LoginHistory extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'ip_address',
        'user_agent',
        'guard',
        'status',
        'logged_in_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
        ];
    }

    /**
     * Utilisateur concerné (si toujours présent).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
