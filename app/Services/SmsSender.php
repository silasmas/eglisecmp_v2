<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SmsSendResult;
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
     * @param  string  $phone  Numero brut du destinataire.
     * @param  string  $message  Corps du message (UTF-8).
     */
    public function send(string $phone, string $message): SmsSendResult
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $message = trim($message);

        if ($normalizedPhone === '') {
            return new SmsSendResult(
                status: SmsSendResult::STATUS_NO_PHONE,
                success: false,
                error: 'Numero de telephone absent ou invalide.',
            );
        }

        if ($message === '') {
            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                error: 'Message SMS vide.',
            );
        }

        $driver = (string) config('sms.driver', 'log');

        return match ($driver) {
            'keccel' => $this->sendViaKeccel($normalizedPhone, $message),
            'http' => $this->sendViaHttp($normalizedPhone, $message),
            default => $this->sendViaLog($normalizedPhone, $message),
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
    private function sendViaLog(string $phone, string $message): SmsSendResult
    {
        Log::info('SMS simule', [
            'to' => $phone,
            'from' => config('sms.from'),
            'message' => $this->fitSingleSms($message),
        ]);

        return new SmsSendResult(
            status: SmsSendResult::STATUS_SIMULATED,
            success: true,
            response: 'SMS journalise (mode log).',
        );
    }

    /**
     * Envoie via l'API Keccel (GET v1/message.asp).
     */
    private function sendViaKeccel(string $phone, string $message): SmsSendResult
    {
        $url = (string) config('sms.keccel.url');
        $token = (string) config('sms.keccel.token');
        $from = (string) config('sms.from');

        if ($url === '' || $token === '') {
            Log::warning('Configuration Keccel incomplete : SMS non envoye.', ['to' => $phone]);

            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                error: 'Configuration Keccel incomplete (SMS_URL ou SMS_TOKEN).',
            );
        }

        if ($from === '') {
            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                error: 'SMS_FROM absent dans la configuration.',
            );
        }

        $payload = [
            'token' => $token,
            'from' => $from,
            'to' => $phone,
            'message' => $this->fitSingleSms($message),
        ];

        try {
            $response = Http::timeout(20)->get($url, $payload);
        } catch (\Throwable $exception) {
            Log::error('Exception envoi SMS Keccel', [
                'to' => $phone,
                'error' => $exception->getMessage(),
            ]);

            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                error: $exception->getMessage(),
            );
        }

        $responseBody = trim($response->body());
        $parsed = $this->parseKeccelResponse($responseBody, $response->status());

        if (! $parsed['success']) {
            Log::error('Echec envoi SMS Keccel', [
                'to' => $phone,
                'status' => $response->status(),
                'body' => $responseBody,
            ]);

            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                response: $responseBody !== '' ? $responseBody : null,
                error: $parsed['error'],
            );
        }

        Log::info('SMS Keccel envoye', [
            'to' => $phone,
            'from' => $from,
            'body' => $responseBody,
        ]);

        return new SmsSendResult(
            status: SmsSendResult::STATUS_SENT,
            success: true,
            response: $parsed['message'],
        );
    }

    /**
     * POST JSON vers une passerelle SMS generique.
     */
    private function sendViaHttp(string $phone, string $message): SmsSendResult
    {
        $url = (string) config('sms.http.url');
        $token = (string) config('sms.http.token');

        if ($url === '') {
            Log::warning('SMS_HTTP_URL absent : SMS non envoye.', ['to' => $phone]);

            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                error: 'SMS_HTTP_URL absent.',
            );
        }

        $request = Http::timeout(20)->acceptJson();

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->post($url, [
                'to' => $phone,
                'from' => config('sms.from'),
                'message' => $this->fitSingleSms($message),
            ]);
        } catch (\Throwable $exception) {
            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                error: $exception->getMessage(),
            );
        }

        $responseBody = trim($response->body());

        if (! $response->successful()) {
            Log::error('Echec envoi SMS HTTP', [
                'to' => $phone,
                'status' => $response->status(),
                'body' => $responseBody,
            ]);

            return new SmsSendResult(
                status: SmsSendResult::STATUS_FAILED,
                success: false,
                response: $responseBody !== '' ? $responseBody : null,
                error: 'Passerelle HTTP : HTTP '.$response->status(),
            );
        }

        return new SmsSendResult(
            status: SmsSendResult::STATUS_SENT,
            success: true,
            response: $responseBody !== '' ? $responseBody : 'OK',
        );
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
     * Analyse la reponse Keccel (CSV v1 ou JSON legacy).
     *
     * @return array{success: bool, message: string, error: string}
     */
    private function parseKeccelResponse(string $body, int $httpStatus): array
    {
        if ($httpStatus < 200 || $httpStatus >= 300) {
            return [
                'success' => false,
                'message' => '',
                'error' => 'Keccel : HTTP '.$httpStatus,
            ];
        }

        $body = trim($body);

        if ($body === '' || str_contains(strtolower($body), 'webhook.site')) {
            return [
                'success' => false,
                'message' => '',
                'error' => 'URL SMS incorrecte. Utilisez https://api.keccel.com/sms/v1/message.asp',
            ];
        }

        if (! str_starts_with($body, '{')) {
            return $this->parseKeccelCsvResponse($body);
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            return [
                'success' => false,
                'message' => '',
                'error' => 'Keccel : reponse invalide.',
            ];
        }

        $status = strtoupper(trim((string) ($decoded['status'] ?? '')));
        $description = trim((string) ($decoded['description'] ?? $decoded['message'] ?? $status));

        if (in_array($status, ['REJECTED', 'FAILED', 'ERROR'], true)) {
            $error = $description !== '' ? $description : $status;

            if (str_contains(strtolower($error), 'missing from parameter')) {
                $error = 'Parametre from non recu par Keccel. Utilisez SMS_URL=https://api.keccel.com/sms/v1/message.asp et verifiez SMS_FROM.';
            }

            return [
                'success' => false,
                'message' => $description,
                'error' => 'Keccel : '.$error,
            ];
        }

        if (in_array($status, ['ACCEPTED', 'SENT', 'SUCCESS', 'OK', '100', '0'], true)) {
            return [
                'success' => true,
                'message' => $description !== '' ? $description : $status,
                'error' => '',
            ];
        }

        return [
            'success' => false,
            'message' => $description,
            'error' => 'Keccel : '.($description !== '' ? $description : 'statut inconnu'),
        ];
    }

    /**
     * Analyse une reponse CSV Keccel v1 : SENT, id, description.
     *
     * @return array{success: bool, message: string, error: string}
     */
    private function parseKeccelCsvResponse(string $body): array
    {
        $parts = array_map('trim', explode(',', $body, 3));
        $status = strtoupper($parts[0] ?? '');
        $reference = $parts[1] ?? '';
        $description = $parts[2] ?? $body;

        if ($status === 'SENT') {
            $message = $description !== '' ? $description : 'SMS envoye';

            if ($reference !== '') {
                $message .= ' (ref. '.$reference.')';
            }

            return [
                'success' => true,
                'message' => $message,
                'error' => '',
            ];
        }

        if ($status === 'REJECTED') {
            return [
                'success' => false,
                'message' => $description,
                'error' => 'Keccel : '.($description !== '' ? $description : 'REJECTED'),
            ];
        }

        return [
            'success' => false,
            'message' => $body,
            'error' => 'Keccel : reponse inattendue.',
        ];
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
