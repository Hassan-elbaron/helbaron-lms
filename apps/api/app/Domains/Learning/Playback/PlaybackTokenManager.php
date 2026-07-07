<?php

namespace App\Domains\Learning\Playback;

use App\Domains\Learning\Contracts\PlaybackTokenProvider;
use App\Domains\Learning\Playback\Providers\CloudFrontPlaybackTokenProvider;
use App\Domains\Learning\Playback\Providers\FakePlaybackTokenProvider;
use App\Domains\Learning\Playback\Providers\MuxPlaybackTokenProvider;
use App\Domains\Learning\Playback\Providers\S3PlaybackTokenProvider;
use App\Platform\Shared\Support\CloudFrontUrlSigner;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the configured PlaybackTokenProvider (fake | s3 | cloudfront | mux). Provider
 * adapters receive vendor config here so no other Learning code reads secrets.
 */
class PlaybackTokenManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(): PlaybackTokenProvider
    {
        return match ((string) config('learning.playback.provider', 'fake')) {
            's3' => $this->app->make(S3PlaybackTokenProvider::class),
            'cloudfront' => new CloudFrontPlaybackTokenProvider($this->cloudFrontSigner()),
            'mux' => new MuxPlaybackTokenProvider((array) config('services.mux')),
            default => $this->app->make(FakePlaybackTokenProvider::class),
        };
    }

    private function cloudFrontSigner(): CloudFrontUrlSigner
    {
        $cf = (array) config('services.cloudfront');
        $key = (string) ($cf['private_key'] ?? '');
        $decoded = base64_decode($key, true);
        $pem = ($decoded !== false && str_contains($decoded, 'PRIVATE KEY')) ? $decoded : $key;

        return new CloudFrontUrlSigner(
            baseUrl: (string) ($cf['url'] ?? ''),
            keyPairId: (string) ($cf['key_pair_id'] ?? ''),
            privateKeyPem: $pem,
        );
    }
}
