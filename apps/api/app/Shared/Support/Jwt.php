<?php

namespace App\Shared\Support;

use RuntimeException;

/**
 * Minimal RS256 JWT signer used by provider adapters (e.g. Mux signed playback). Deliberately
 * tiny and dependency-free — only signing is needed. Not for verifying third-party tokens.
 */
final class Jwt
{
    /**
     * Sign claims with an RSA private key (PEM) using RS256.
     *
     * @param  array<string, mixed>  $claims
     * @param  array<string, mixed>  $headerExtra
     */
    public static function rs256(array $claims, string $privateKeyPem, array $headerExtra = []): string
    {
        $header = self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT'] + $headerExtra, JSON_UNESCAPED_SLASHES));
        $payload = self::b64(json_encode($claims, JSON_UNESCAPED_SLASHES));
        $signingInput = $header.'.'.$payload;

        $key = openssl_pkey_get_private($privateKeyPem);

        if ($key === false) {
            throw new RuntimeException('Invalid RSA private key for JWT signing.');
        }

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign JWT.');
        }

        return $signingInput.'.'.self::b64($signature);
    }

    /** URL-safe base64 without padding (JWT/base64url). */
    public static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
