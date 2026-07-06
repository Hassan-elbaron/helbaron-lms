<?php

namespace App\Shared\Support;

use RuntimeException;

/**
 * Signs private CloudFront URLs with a canned policy (RSA-SHA1 over the policy statement), the
 * scheme CloudFront expects. Dependency-free; the private key never leaves the server and is
 * never returned to callers. Only the signed URL is exposed.
 */
final class CloudFrontUrlSigner
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $keyPairId,
        private readonly string $privateKeyPem,
    ) {}

    /** Sign a resource path (e.g. "media/x.mp4") until the given unix expiry. */
    public function sign(string $path, int $expiresAt): string
    {
        if ($this->keyPairId === '' || $this->privateKeyPem === '') {
            throw new RuntimeException('CloudFront signing is not configured (set CLOUDFRONT_KEY_PAIR_ID and CLOUDFRONT_PRIVATE_KEY).');
        }

        $resource = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');

        $policy = json_encode([
            'Statement' => [[
                'Resource' => $resource,
                'Condition' => ['DateLessThan' => ['AWS:EpochTime' => $expiresAt]],
            ]],
        ], JSON_UNESCAPED_SLASHES);

        $key = openssl_pkey_get_private($this->privateKeyPem);

        if ($key === false) {
            throw new RuntimeException('Invalid CloudFront private key.');
        }

        $signature = '';
        if (! openssl_sign($policy, $signature, $key, OPENSSL_ALGO_SHA1)) {
            throw new RuntimeException('Failed to sign CloudFront URL.');
        }

        $query = http_build_query([
            'Expires' => $expiresAt,
            'Signature' => $this->cfEncode($signature),
            'Key-Pair-Id' => $this->keyPairId,
        ], '', '&', PHP_QUERY_RFC3986);

        return $resource.'?'.$query;
    }

    /** CloudFront's URL-safe base64 variant (+/= -> -~_). */
    private function cfEncode(string $data): string
    {
        return strtr(base64_encode($data), '+/=', '-~_');
    }
}
