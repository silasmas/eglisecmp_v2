<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Lettre d’invitation PDF (modèle projet ou override pasteur).
 *
 * @property int $id
 * @property int $project_id
 * @property int|null $guest_pastor_id
 * @property string|null $recipient_title
 * @property string|null $body_html
 * @property string|null $signature_html
 * @property string|null $header_image_path
 * @property string|null $pdf_path
 * @property string $status
 * @property Carbon|null $generated_at
 * @property int|null $generated_by_user_id
 */
class GuestInvitationLetter extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'project_id',
        'guest_pastor_id',
        'recipient_title',
        'body_html',
        'signature_html',
        'header_image_path',
        'pdf_path',
        'status',
        'generated_at',
        'generated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    /**
     * Indique si c’est le modèle projet (sans pasteur).
     */
    public function isProjectTemplate(): bool
    {
        return $this->guest_pastor_id === null;
    }

    /**
     * URL publique du PDF généré.
     */
    public function pdfPublicUrl(): ?string
    {
        if (blank($this->pdf_path)) {
            return null;
        }

        return Storage::disk('public')->url((string) $this->pdf_path);
    }

    /**
     * @return BelongsTo<GuestPastoralProject, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(GuestPastoralProject::class, 'project_id');
    }

    /**
     * @return BelongsTo<GuestPastor, $this>
     */
    public function guestPastor(): BelongsTo
    {
        return $this->belongsTo(GuestPastor::class, 'guest_pastor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}
