<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Témoignage affiché sur le mur public (post-it texte, vidéo ou mixte).
 *
 * @property int $id
 * @property string $kind
 * @property string $first_name
 * @property string|null $last_name
 * @property string $title
 * @property string|null $text
 * @property string|null $video
 * @property string|null $postit_color
 * @property string|null $font_family
 * @property string|null $category
 * @property string|null $email
 * @property string|null $phone
 * @property string $verification_type
 * @property string $status
 * @property Carbon|null $approved_at
 * @property bool $is_anonymous
 * @property string|null $video_file
 * @property string|null $rejection_reason
 * @property int $share_count
 */
class Testimony extends Model
{
    public const KIND_TEXT = 'text';

    public const KIND_VIDEO = 'video';

    public const KIND_MIX = 'mix';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const VERIFY_EMAIL = 'email';

    public const VERIFY_PHONE = 'phone';

    public const VERIFY_BOTH = 'both';

    protected $fillable = [
        'kind',
        'first_name',
        'last_name',
        'title',
        'text',
        'video',
        'postit_color',
        'font_family',
        'category',
        'email',
        'phone',
        'verification_type',
        'is_anonymous',
        'video_file',
        'status',
        'approved_at',
        'rejection_reason',
        'share_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'share_count' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Clé de réaction SPA pour ce témoignage.
     */
    public function reactableKey(): string
    {
        return ContentReaction::reactableKey('testimony', $this->id);
    }

    /**
     * Nom affiché sur le mur public.
     */
    public function publicAuthorName(): string
    {
        if ($this->is_anonymous) {
            return 'Anonyme';
        }

        return $this->authorFullName();
    }

    /**
     * Images jointes au témoignage (mur mixte ou illustré).
     *
     * @return HasMany<TestimonyImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(TestimonyImage::class);
    }

    /**
     * Nom complet de l’auteur pour l’affichage public.
     */
    public function authorFullName(): string
    {
        return trim($this->first_name.' '.(string) $this->last_name);
    }

    /**
     * Indique si le témoignage peut être approuvé depuis l’admin.
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Indique si le témoignage peut être rejeté depuis l’admin.
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED], true);
    }
}
