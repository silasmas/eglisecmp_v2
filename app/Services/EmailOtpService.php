<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\WorkerEmailOtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Génération, envoi e-mail et vérification des codes OTP.
 */
final class EmailOtpService
{
    /**
     * Crée un OTP et l'envoie par e-mail.
     *
     * @return array{otp: EmailOtp}
     */
    public function send(string $email, string $purpose): array
    {
        $normalized = $this->normalizeEmail($email);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'email' => 'Adresse e-mail invalide.',
            ]);
        }

        EmailOtp::query()
            ->where('email', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $length = 6;
        $ttl = 15;
        $code = $this->generateCode($length);

        $otp = EmailOtp::query()->create([
            'email' => $normalized,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
            'attempts' => 0,
        ]);

        Mail::to($normalized)->send(new WorkerEmailOtpMail($code, $ttl));

        return ['otp' => $otp];
    }

    /**
     * Vérifie un code OTP e-mail.
     */
    public function verify(string $email, string $purpose, string $code): EmailOtp
    {
        $normalized = $this->normalizeEmail($email);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'email' => 'Adresse e-mail invalide.',
            ]);
        }

        $otp = EmailOtp::query()
            ->where('email', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();

        if ($otp === null || ! $otp->isValid()) {
            throw ValidationException::withMessages([
                'otp_code' => 'Code expiré ou introuvable. Demandez un nouveau code.',
            ]);
        }

        if ($otp->attempts >= 5) {
            throw ValidationException::withMessages([
                'otp_code' => 'Trop de tentatives. Demandez un nouveau code.',
            ]);
        }

        $otp->attempts = $otp->attempts + 1;
        $otp->save();

        if (! Hash::check(trim($code), $otp->code_hash)) {
            throw ValidationException::withMessages([
                'otp_code' => 'Code incorrect.',
            ]);
        }

        $otp->verified_at = now();
        $otp->save();

        return $otp;
    }

    /**
     * OTP vérifié récemment pour cet e-mail.
     */
    public function hasVerifiedRecently(string $email, string $purpose, int $withinMinutes = 60): bool
    {
        $normalized = $this->normalizeEmail($email);

        if ($normalized === '') {
            return false;
        }

        return EmailOtp::query()
            ->where('email', $normalized)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes($withinMinutes))
            ->exists();
    }

    /**
     * Normalise une adresse e-mail.
     */
    public function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /**
     * Génère un code numérique.
     */
    private function generateCode(int $length): string
    {
        $max = (10 ** $length) - 1;
        $code = (string) random_int(0, $max);

        return str_pad($code, $length, '0', STR_PAD_LEFT);
    }
}
