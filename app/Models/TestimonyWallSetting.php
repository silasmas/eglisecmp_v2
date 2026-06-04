<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Réglages globaux du mur de témoignages (ligne unique en base).
 *
 * @property int $id
 * @property bool $allow_photo_upload
 * @property int $max_photos_per_testimony
 * @property bool $allow_youtube_link
 * @property bool $allow_video_upload
 * @property int $max_video_upload_mb
 * @property bool $allow_anonymous
 * @property bool $require_first_name
 * @property bool $require_last_name
 */
class TestimonyWallSetting extends Model
{
    protected $fillable = [
        'allow_photo_upload',
        'max_photos_per_testimony',
        'allow_youtube_link',
        'allow_video_upload',
        'max_video_upload_mb',
        'allow_anonymous',
        'require_first_name',
        'require_last_name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allow_photo_upload' => 'boolean',
            'max_photos_per_testimony' => 'integer',
            'allow_youtube_link' => 'boolean',
            'allow_video_upload' => 'boolean',
            'max_video_upload_mb' => 'integer',
            'allow_anonymous' => 'boolean',
            'require_first_name' => 'boolean',
            'require_last_name' => 'boolean',
        ];
    }

    /**
     * Retourne la configuration courante (crée la ligne par défaut si absente).
     */
    public static function current(): self
    {
        $row = self::query()->first();

        if ($row !== null) {
            return $row;
        }

        return self::query()->create(self::defaultAttributes());
    }

    /**
     * Valeurs par défaut pour une nouvelle ligne de réglages.
     *
     * @return array<string, mixed>
     */
    public static function defaultAttributes(): array
    {
        return [
            'allow_photo_upload' => true,
            'max_photos_per_testimony' => 5,
            'allow_youtube_link' => true,
            'allow_video_upload' => true,
            'max_video_upload_mb' => 5,
            'allow_anonymous' => true,
            'require_first_name' => true,
            'require_last_name' => false,
        ];
    }

    /**
     * Sérialise les réglages pour l’API publique et la SPA.
     *
     * @return array<string, bool|int>
     */
    public function toPublicArray(): array
    {
        return [
            'allowPhotoUpload' => (bool) $this->allow_photo_upload,
            'maxPhotosPerTestimony' => max(1, min(20, (int) $this->max_photos_per_testimony)),
            'allowYoutubeLink' => (bool) $this->allow_youtube_link,
            'allowVideoUpload' => (bool) $this->allow_video_upload,
            'maxVideoUploadMb' => max(1, min(100, (int) $this->max_video_upload_mb)),
            'allowAnonymous' => (bool) $this->allow_anonymous,
            'requireFirstName' => (bool) $this->require_first_name,
            'requireLastName' => (bool) $this->require_last_name,
        ];
    }
}
