<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Validation et normalisation Mobile Money FlexPay (opérateurs UI + MSISDN 243…).
 */
final class FlexPayMobileValidator
{
    /**
     * Retourne la liste des opérateurs configurés pour l’interface publique.
     *
     * @return list<array{type: string, code: string, label: string, msisdn_regex: string}>
     */
    public static function listProviders(): array
    {
        /** @var mixed $configured */
        $configured = config('flexpay.flexpay_mobile_providers', []);

        if (! is_array($configured)) {
            return [];
        }

        $providers = [];

        foreach ($configured as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtolower(trim((string) ($row['code'] ?? $row['type'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));
            $regex = trim((string) ($row['msisdn_regex'] ?? ''));

            if ($code === '' || $label === '' || $regex === '') {
                continue;
            }

            $providers[] = [
                'type' => strtolower(trim((string) ($row['type'] ?? $code))),
                'code' => $code,
                'label' => $label,
                'msisdn_regex' => $regex,
            ];
        }

        return $providers;
    }

    /**
     * Recherche un opérateur par code interne (mpesa, airtel…).
     *
     * @return array{type: string, code: string, label: string, msisdn_regex: string}|null
     */
    public static function findProvider(string $providerCode): ?array
    {
        $needle = strtolower(trim($providerCode));

        if ($needle === '') {
            return null;
        }

        foreach (self::listProviders() as $provider) {
            if ($provider['code'] === $needle || $provider['type'] === $needle) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Normalise un numéro RD Congo en MSISDN 12 chiffres (243 + 9 chiffres).
     *
     * @throws InvalidArgumentException Si le format est invalide.
     */
    public static function normalizeMsisdn(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if ($digits === '') {
            throw new InvalidArgumentException('Numéro Mobile money requis.');
        }

        if (str_starts_with($digits, '243')) {
            if (preg_match('/^243\d{9}$/', $digits) !== 1) {
                throw new InvalidArgumentException('Format attendu : 243 suivi de 9 chiffres (ex. 243891234567).');
            }

            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            $rest = substr($digits, 1);
            if (preg_match('/^\d{9}$/', $rest) !== 1) {
                throw new InvalidArgumentException('Numéro national invalide : 10 chiffres commençant par 0.');
            }

            return '243'.$rest;
        }

        if (preg_match('/^\d{9}$/', $digits) === 1) {
            return '243'.$digits;
        }

        throw new InvalidArgumentException('Format attendu : 243XXXXXXXXX ou 0XXXXXXXXX.');
    }

    /**
     * Vérifie que le MSISDN correspond à l’opérateur sélectionné (regex UI).
     *
     * @throws InvalidArgumentException Si l’opérateur est inconnu ou le numéro ne correspond pas.
     */
    public static function assertMsisdnMatchesProvider(string $providerCode, string $normalizedPhone): void
    {
        $provider = self::findProvider($providerCode);

        if ($provider === null) {
            throw new InvalidArgumentException('Opérateur Mobile money non reconnu.');
        }

        $pattern = '/'.$provider['msisdn_regex'].'/';

        if (preg_match($pattern, $normalizedPhone) !== 1) {
            throw new InvalidArgumentException(
                sprintf('Le numéro ne correspond pas au réseau %s.', $provider['label'])
            );
        }
    }
}
