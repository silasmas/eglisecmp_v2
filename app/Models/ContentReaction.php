<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Réaction anonyme d’un visiteur sur un contenu identifié par une clé composite (ex. post:12).
 *
 * @property int $id
 * @property string $reactable_key
 * @property string $reaction_key
 * @property string $visitor_token UUID du navigateur (localStorage).
 */
class ContentReaction extends Model
{
    protected $fillable = [
        'reactable_key',
        'reaction_key',
        'visitor_token',
    ];

    /**
     * Construit la clé stable pour un type de modèle et son identifiant.
     *
     * @param  string  $type  Préfixe (post, daily_verse, schedule_program).
     * @param  int|string  $id  Identifiant numérique.
     * @return string Clé au format « type:id ».
     */
    public static function reactableKey(string $type, int|string $id): string
    {
        return $type.':'.(string) $id;
    }
}
