<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TestimonyApprovedMail;
use App\Mail\TestimonyRejectedMail;
use App\Models\Testimony;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie les courriels aux fidèles lors des décisions de modération sur un témoignage.
 */
final class TestimonyNotificationService
{
    /**
     * Informe le fidèle que son témoignage est publié.
     */
    public function notifyApproved(Testimony $testimony): void
    {
        $this->sendToFaithful($testimony, new TestimonyApprovedMail($testimony));
    }

    /**
     * Informe le fidèle que son témoignage est refusé (avec motif).
     */
    public function notifyRejected(Testimony $testimony): void
    {
        $this->sendToFaithful($testimony, new TestimonyRejectedMail($testimony));
    }

    /**
     * @param  TestimonyApprovedMail|TestimonyRejectedMail  $mailable
     */
    private function sendToFaithful(Testimony $testimony, object $mailable): void
    {
        $email = trim((string) $testimony->email);

        if ($email === '') {
            return;
        }

        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $exception) {
            Log::error('Courriel témoignage impossible.', [
                'testimony_id' => $testimony->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
