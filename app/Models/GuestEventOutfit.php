<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Tenue prévue pour une session d’événement (affichée sur le portail invité).
 *
 * @property int $id
 * @property int $project_id
 * @property string $session_key
 * @property string $title
 * @property string|null $description
 * @property string|null $image_path
 * @property int $sort_order
 */
class GuestEventOutfit extends Model
{
    public const SESSION_MATIN = 'matin';

    public const SESSION_MIDI = 'midi';

    public const SESSION_SOIR = 'soir';

    public const SESSION_SAMEDI = 'samedi';

    protected $fillable = [
        'project_id',
        'session_key',
        'title',
        'description',
        'image_path',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sessionOptions(): array
    {
        return [
            self::SESSION_MATIN => 'Session Matin',
            self::SESSION_MIDI => 'Session Midi',
            self::SESSION_SOIR => 'Session Soir',
            self::SESSION_SAMEDI => 'Session Samedi',
        ];
    }

    /**
     * URL publique de l’image de tenue.
     */
    public function imagePublicUrl(): ?string
    {
        if (blank($this->image_path)) {
            return null;
        }

        $path = (string) $this->image_path;
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return BelongsTo<GuestPastoralProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(GuestPastoralProject::class, 'project_id');
    }
}
