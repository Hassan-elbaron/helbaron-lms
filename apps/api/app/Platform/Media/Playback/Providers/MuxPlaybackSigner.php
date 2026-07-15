<?php

namespace App\Platform\Media\Playback\Providers;

use App\Platform\Shared\Media\Contracts\PlaybackPort;
use App\Platform\Shared\Media\Data\MediaAssetRef;
use App\Platform\Shared\Media\Data\PlaybackToken;
use App\Platform\Shared\Media\Exceptions\MediaUnavailableException;
use App\Platform\Shared\Support\Jwt;
use RuntimeException;

/**
 * Real Mux signed playback. Builds an RS256 JWT over the PUBLIC playback id (never the raw asset
 * id) and returns only the signed stream URL. Consumes a MediaAssetRef; identical output to the
 * former Learning MuxPlaybackTokenProvider.
 */
class MuxPlaybackSigner implements PlaybackPort
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    public function issue(MediaAssetRef $asset, int $ttlSeconds): PlaybackToken
    {
        if ($asset->playbackId === null) {
            throw new MediaUnavailableException;
        }

        $keyId = (string) ($this->config['signing_key_id'] ?? '');
        $keyB64 = (string) ($this->config['signing_key'] ?? '');

        if ($keyId === '' || $keyB64 === '') {
            throw new RuntimeException('Mux signing is not configured (set MUX_SIGNING_KEY_ID and MUX_SIGNING_KEY).');
        }

        $expires = now()->addSeconds($ttlSeconds);

        $jwt = Jwt::rs256(
            claims: [
                'sub' => $asset->playbackId,
                'aud' => (string) ($this->config['audience'] ?? 'v'),
                'exp' => $expires->getTimestamp(),
                'kid' => $keyId,
            ],
            privateKeyPem: $this->privateKey($keyB64),
            headerExtra: ['kid' => $keyId],
        );

        $base = rtrim((string) ($this->config['stream_base_url'] ?? 'https://stream.mux.com'), '/');
        $url = "{$base}/{$asset->playbackId}.m3u8?token={$jwt}";

        return new PlaybackToken(url: $url, expiresAt: $expires, kind: 'video');
    }

    /** Mux stores the signing key base64-encoded; accept raw PEM too. */
    private function privateKey(string $value): string
    {
        $decoded = base64_decode($value, true);

        return ($decoded !== false && str_contains($decoded, 'PRIVATE KEY')) ? $decoded : $value;
    }
}
