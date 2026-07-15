<?php

namespace App\Platform\Media\Playback;

use App\Platform\Media\Playback\Providers\CloudFrontPlaybackSigner;
use App\Platform\Media\Playback\Providers\FakePlaybackSigner;
use App\Platform\Media\Playback\Providers\MuxPlaybackSigner;
use App\Platform\Media\Playback\Providers\S3PlaybackSigner;
use App\Platform\Shared\Media\Contracts\PlaybackPort;
use App\Platform\Shared\Support\CloudFrontUrlSigner;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves the configured PlaybackPort signer (fake | s3 | cloudfront | mux). Signer adapters
 * receive vendor config here so no other code reads secrets. Relocated from Learning to the Media
 * platform; configuration keys are unchanged (learning.playback.provider, services.mux/cloudfront).
 */
class PlaybackTokenManager
{
    public function __construct(private readonly Container $app) {}

    public function resolve(): PlaybackPort
    {
        return match ((string) config('learning.playback.provider', 'fake')) {
            's3' => $this->app->make(S3PlaybackSigner::class),
            'cloudfront' => new CloudFrontPlaybackSigner($this->cloudFrontSigner()),
            'mux' => new MuxPlaybackSigner((array) config('services.mux')),
            default => $this->app->make(FakePlaybackSigner::class),
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
