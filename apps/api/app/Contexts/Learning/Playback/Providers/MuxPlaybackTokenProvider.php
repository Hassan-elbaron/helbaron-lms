<?php

namespace App\Contexts\Learning\Playback\Providers;

use App\Domains\Authoring\Models\LessonMedia;
use App\Contexts\Learning\Contracts\PlaybackTokenProvider;
use App\Contexts\Learning\Exceptions\MediaUnavailableException;
use App\Contexts\Learning\Playback\Data\PlaybackToken;
use App\Platform\Shared\Support\Jwt;
use RuntimeException;

/**
 * Real Mux signed playback. Builds an RS256 JWT over the PUBLIC playback id (never the
 * mux_asset_id) and returns only the signed stream URL. The asset id stays server-side.
 */
class MuxPlaybackTokenProvider implements PlaybackTokenProvider
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    public function issue(LessonMedia $media, int $ttlSeconds): PlaybackToken
    {
        if ($media->mux_playback_id === null) {
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
                'sub' => $media->mux_playback_id,
                'aud' => (string) ($this->config['audience'] ?? 'v'),
                'exp' => $expires->getTimestamp(),
                'kid' => $keyId,
            ],
            privateKeyPem: $this->privateKey($keyB64),
            headerExtra: ['kid' => $keyId],
        );

        $base = rtrim((string) ($this->config['stream_base_url'] ?? 'https://stream.mux.com'), '/');
        $url = "{$base}/{$media->mux_playback_id}.m3u8?token={$jwt}";

        return new PlaybackToken(url: $url, expiresAt: $expires, kind: 'video');
    }

    /** Mux stores the signing key base64-encoded; accept raw PEM too. */
    private function privateKey(string $value): string
    {
        $decoded = base64_decode($value, true);

        return ($decoded !== false && str_contains($decoded, 'PRIVATE KEY')) ? $decoded : $value;
    }
}
