<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PhoneOtp;
use App\Support\SmsSendResult;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Génération, envoi SMS et vérification des codes OTP téléphoniques.
 */
final class PhoneOtpService
{
    public function __construct(
        private readonly SmsSender $smsSender,
    ) {}

    /**
     * Crée un OTP, l'envoie par SMS et retourne le résultat d'envoi.
     *
     * @param  string  $phone  Numéro brut du destinataire.
     * @param  string  $purpose  Usage métier (ex. child_presentation).
     * @return array{otp: PhoneOtp, sms: SmsSendResult}
     */
    public function send(string $phone, string $purpose): array
    {
        $normalized = $this->normalizePhone($phone);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'phone' => 'Numéro de téléphone invalide.',
            ]);
        }

        PhoneOtp::query()
            ->where('phone', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $length = max(4, (int) config('child_presentation.otp_length', 6));
        $ttl = max(1, (int) config('child_presentation.otp_ttl_minutes', 10));
        $code = $this->generateCode($length);

        $otp = PhoneOtp::query()->create([
            'phone' => $normalized,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttl),
            'attempts' => 0,
        ]);

        $message = $this->smsSender->fitSingleSms(
            "CMP: votre code de verification est {$code}. Valide {$ttl} min."
        );

        $sms = $this->smsSender->send($normalized, $message);

        return [
            'otp' => $otp,
            'sms' => $sms,
        ];
    }

    /**
     * Vérifie un code OTP ; lève une ValidationException en cas d'échec.
     *
     * @param  string  $phone  Numéro brut.
     * @param  string  $purpose  Usage métier.
     * @param  string  $code  Code saisi par l'utilisateur.
     * @return PhoneOtp OTP marqué comme vérifié.
     */
    public function verify(string $phone, string $purpose, string $code): PhoneOtp
    {
        $normalized = $this->normalizePhone($phone);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'phone' => 'Numéro de téléphone invalide.',
            ]);
        }

        $otp = PhoneOtp::query()
            ->where('phone', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('id')
            ->first();

        if ($otp === null || ! $otp->isValid()) {
            throw ValidationException::withMessages([
                'otp_code' => 'Code expiré ou introuvable. Demandez un nouveau code.',
            ]);
        }

        $maxAttempts = max(1, (int) config('child_presentation.otp_max_attempts', 5));

        if ($otp->attempts >= $maxAttempts) {
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
     * Indique si un OTP vérifié récemment existe encore pour ce numéro.
     *
     * @param  string  $phone  Numéro brut.
     * @param  string  $purpose  Usage métier.
     * @param  int  $withinMinutes  Fenêtre de validité post-vérification.
     */
    public function hasVerifiedRecently(string $phone, string $purpose, int $withinMinutes = 60): bool
    {
        $normalized = $this->normalizePhone($phone);

        if ($normalized === '') {
            return false;
        }

        return PhoneOtp::query()
            ->where('phone', $normalized)
            ->where('purpose', $purpose)
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes($withinMinutes))
            ->exists();
    }

    /**
     * Normalise un numéro congolais au format 243XXXXXXXXX.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '243'.substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '8')) {
            $digits = '243'.$digits;
        }

        return $digits;
    }

    /**
     * Génère un code numérique à longueur fixe.
     */
    private function generateCode(int $length): string
    {
        $max = (10 ** $length) - 1;
        $code = (string) random_int(0, $max);

        return str_pad($code, $length, '0', STR_PAD_LEFT);
    }
}
