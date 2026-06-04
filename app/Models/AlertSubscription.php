<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Abonnement opt-in aux notifications live YouTube et événements CMP.
 *
 * @property int $id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $name
 * @property bool $notify_live
 * @property bool $notify_events
 * @property string $source
 * @property string $unsubscribe_token
 */
class AlertSubscription extends Model
{
    public const SOURCE_TESTIMONY = 'testimony';

    public const SOURCE_EVENTS = 'events';

    public const SOURCE_LIVE = 'live';

    public const SOURCE_FOOTER = 'footer';

    protected $fillable = [
        'email',
        'phone',
        'name',
        'notify_live',
        'notify_events',
        'source',
        'unsubscribe_token',
    ];

    protected function casts(): array
    {
        return [
            'notify_live' => 'boolean',
            'notify_events' => 'boolean',
        ];
    }

    /**
     * Abonnés actifs aux alertes live (email et/ou téléphone).
     *
     * @param  Builder<AlertSubscription>  $query
     * @return Builder<AlertSubscription>
     */
    public function scopeForLiveAlerts(Builder $query): Builder
    {
        return $query
            ->where('notify_live', true)
            ->where(function (Builder $inner): void {
                $inner->where(function (Builder $e): void {
                    $e->whereNotNull('email')->where('email', '!=', '');
                })->orWhere(function (Builder $p): void {
                    $p->whereNotNull('phone')->where('phone', '!=', '');
                });
            });
    }

    /**
     * Abonnés actifs aux alertes événements.
     *
     * @param  Builder<AlertSubscription>  $query
     * @return Builder<AlertSubscription>
     */
    public function scopeForEventAlerts(Builder $query): Builder
    {
        return $query
            ->where('notify_events', true)
            ->where(function (Builder $inner): void {
                $inner->where(function (Builder $e): void {
                    $e->whereNotNull('email')->where('email', '!=', '');
                })->orWhere(function (Builder $p): void {
                    $p->whereNotNull('phone')->where('phone', '!=', '');
                });
            });
    }

    /**
     * Génère un jeton de désabonnement unique.
     */
    public static function newUnsubscribeToken(): string
    {
        do {
            $token = (string) Str::uuid();
        } while (self::query()->where('unsubscribe_token', $token)->exists());

        return $token;
    }
}
