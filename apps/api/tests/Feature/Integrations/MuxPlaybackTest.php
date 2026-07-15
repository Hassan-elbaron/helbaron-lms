<?php

use App\Platform\Media\Playback\PlaybackTokenManager;
use App\Platform\Media\Playback\Providers\CloudFrontPlaybackSigner;
use App\Platform\Media\Playback\Providers\FakePlaybackSigner;
use App\Platform\Media\Playback\Providers\MuxPlaybackSigner;
use App\Platform\Shared\Media\Data\MediaAccessPolicy;
use App\Platform\Shared\Media\Data\MediaAssetRef;

function b64urlDecode(string $s): string
{
    return (string) base64_decode(strtr($s, '-_', '+/').str_repeat('=', (4 - strlen($s) % 4) % 4));
}

function muxAssetRef(): MediaAssetRef
{
    // A ref carrying the PUBLIC playback id + a storage key — never the raw Mux asset id, which
    // is structurally absent from MediaAssetRef.
    return new MediaAssetRef(
        id: 'lm_public',
        provider: 'mux',
        playbackId: 'pb_public',
        storageKey: 'media/x.mp4',
        mimeType: 'video/mp4',
        durationSeconds: 120,
        policy: new MediaAccessPolicy(signed: true, visibility: 'private'),
        metadata: [],
    );
}

it('issues a Mux signed playback URL that exposes the playback id but never the asset id', function () {
    $keypair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keypair, $privatePem);
    $publicPem = openssl_pkey_get_details($keypair)['key'];

    $config = [
        'signing_key_id' => 'key_abc',
        'signing_key' => base64_encode($privatePem),
        'stream_base_url' => 'https://stream.mux.com',
        'audience' => 'v',
    ];

    $token = (new MuxPlaybackSigner($config))->issue(muxAssetRef(), 600);

    expect($token->url)->toContain('pb_public')
        ->and($token->url)->not->toContain('asset_secret')
        ->and($token->url)->toContain('token=')
        ->and($token->kind)->toBe('video')
        ->and($token->expiresAt->isFuture())->toBeTrue();

    // The token is a real RS256 JWT that verifies against the public key.
    $jwt = explode('token=', $token->url)[1];
    [$h, $p, $sig] = explode('.', $jwt);
    $ok = openssl_verify("{$h}.{$p}", b64urlDecode($sig), $publicPem, OPENSSL_ALGO_SHA256);
    $claims = json_decode(b64urlDecode($p), true);

    expect($ok)->toBe(1)
        ->and($claims['sub'])->toBe('pb_public')
        ->and($claims['kid'])->toBe('key_abc');
});

it('selects the playback provider by config', function () {
    config()->set('learning.playback.provider', 'fake');
    expect(app(PlaybackTokenManager::class)->resolve())
        ->toBeInstanceOf(FakePlaybackSigner::class);

    config()->set('learning.playback.provider', 'mux');
    config()->set('services.mux', ['signing_key_id' => 'k', 'signing_key' => 'x']);
    expect(app(PlaybackTokenManager::class)->resolve())->toBeInstanceOf(MuxPlaybackSigner::class);

    config()->set('learning.playback.provider', 'cloudfront');
    expect(app(PlaybackTokenManager::class)->resolve())
        ->toBeInstanceOf(CloudFrontPlaybackSigner::class);
});
