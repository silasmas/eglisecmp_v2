<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envoi de SMS (journal local, Keccel ou passerelle HTTP generique).
 */
final class SmsSender
{
    /**
     * Envoie un SMS au numero fourni.
     *
     * @param  string  $phone  Numero du destinataire.
     * @param  string  $message  Corps du message (UTF-8).
     * @return bool True si l'envoi a reussi ou a ete journalise.
     */
    public function send(string $phone, string $message): bool
    {
        $phone = $this->normalizePhone($phone);
        $message = trim($message);

        if ($phone === '' || $message === '') {
            return false;
        }

        $driver = (string) config('sms.driver', 'log');

        return match ($driver) {
            'keccel' => $this->sendViaKeccel($phone, $message),
            'http' => $this->sendViaHttp($phone, $message),
            default => $this->sendViaLog($phone, $message),
        };
    }

    /**
     * Reduit le texte pour tenir dans un seul SMS GSM (160 caracteres sans accents).
     */
    public function fitSingleSms(string $message): string
    {
        $message = trim($this->removeAccents($message));
        $maxLength = max(1, (int) config('sms.max_length', 160));

        if (strlen($message) <= $maxLength) {
            return $message;
        }

        return rtrim(substr($message, 0, $maxLength - 3)).'...';
    }

    /**
     * Journalise le SMS (environnement local).
     */
    private function sendViaLog(string $phone, string $message): bool
    {
        Log::info('SMS simule', [
            'to' => $phone,
            'from' => config('sms.from'),
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Envoie via l'API Keccel (GET message.asp).
     */
    private function sendViaKeccel(string $phone, string $message): bool
    {
        $url = (string) config('sms.keccel.url');
        $token = (string) config('sms.keccel.token');
        $from = (string) config('sms.from');

        if ($url === '' || $token === '') {
            Log::warning('Configuration Keccel incomplete : SMS non envoye.', ['to' => $phone]);

            return false;
        }

        $payload = [
            'token' => $token,
            'from' => $from,
            'to' => $phone,
            'message' => $this->fitSingleSms($message),
        ];

        $response = Http::timeout(20)
            ->acceptJson()
            ->get($url, $payload);

        if (! $this->isKeccelSuccess($response->body(), $response->status())) {
            Log::error('Echec envoi SMS Keccel', [
                'to' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        Log::info('SMS Keccel envoye', [
            'to' => $phone,
            'from' => $from,
            'body' => trim($response->body()),
        ]);

        return true;
    }

    /**
     * POST JSON vers une passerelle SMS generique.
     */
    private function sendViaHttp(string $phone, string $message): bool
    {
        $url = (string) config('sms.http.url');
        $token = (string) config('sms.http.token');

        if ($url === '') {
            Log::warning('SMS_HTTP_URL absent : SMS non envoye.', ['to' => $phone]);

            return false;
        }

        $request = Http::timeout(20)->acceptJson();

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'to' => $phone,
            'from' => config('sms.from'),
            'message' => $this->fitSingleSms($message),
        ]);

        if (! $response->successful()) {
            Log::error('Echec envoi SMS HTTP', [
                'to' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Normalise un numero congolais au format 243XXXXXXXXX attendu par Keccel.
     */
    private function normalizePhone(string $phone): string
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
     * Interprete la reponse texte ou JSON de Keccel.
     */
    private function isKeccelSuccess(string $body, int $status): bool
    {
        if ($status < 200 || $status >= 300) {
            return false;
        }

        $body = trim($body);

        if ($body === '' || str_contains(strtolower($body), 'webhook.site')) {
            return false;
        }

        $decoded = json_decode($body, true);

        if (is_array($decoded)) {
            $code = $decoded['code'] ?? $decoded['status'] ?? $decoded['result'] ?? null;

            if (is_string($code) || is_int($code)) {
                $normalizedCode = strtolower(trim((string) $code));

                if (in_array($normalizedCode, ['0', '100', 'ok', 'success', 'sent'], true)) {
                    return true;
                }

                if (in_array($normalizedCode, ['error', 'failed', 'fail'], true)) {
                    return false;
                }
            }

            if (isset($decoded['success'])) {
                return (bool) $decoded['success'];
            }
        }

        $lower = strtolower($body);

        if (in_array($lower, ['0', '100', 'ok', 'success', 'sent'], true)) {
            return true;
        }

        if (str_contains($lower, 'error') || str_contains($lower, 'fail') || str_contains($lower, 'invalid')) {
            return false;
        }

        if (str_contains($lower, 'success') || str_contains($lower, 'sent')) {
            return true;
        }

        return preg_match('/^\d+$/', $body) === 1 && in_array((int) $body, [0, 100], true);
    }

    /**
     * Retire les accents pour maximiser la limite d'un SMS GSM (160 caracteres).
     */
    private function removeAccents(string $value): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($transliterated === false) {
            return $value;
        }

        return preg_replace("/['`^~]/", '', $transliterated) ?? $transliterated;
    }
}
