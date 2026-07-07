<?php

use App\Domains\Authoring\Models\LessonMedia;
use App\Contexts\Learning\Playback\PlaybackTokenManager;
use App\Contexts\Learning\Playback\Providers\CloudFrontPlaybackTokenProvider;
use App\Contexts\Learning\Playback\Providers\FakePlaybackTokenProvider;
use App\Contexts\Learning\Playback\Providers\MuxPlaybackTokenProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function b64urlDecode(string $s): string
{
    return (string) base64_decode(strtr($s, '-_', '+/').str_repeat('=', (4 - strlen($s) % 4) % 4));
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

    $media = LessonMedia::factory()->create([
        'mux_playback_id' => 'pb_public', 'mux_asset_id' => 'asset_secret', 's3_key' => 'media/x.mp4',
    ]);

    $token = (new MuxPlaybackTokenProvider($config))->issue($media, 600);

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
        ->toBeInstanceOf(FakePlaybackTokenProvider::class);

    config()->set('learning.playback.provider', 'mux');
    config()->set('services.mux', ['signing_key_id' => 'k', 'signing_key' => 'x']);
    expect(app(PlaybackTokenManager::class)->resolve())->toBeInstanceOf(MuxPlaybackTokenProvider::class);

    config()->set('learning.playback.provider', 'cloudfront');
    expect(app(PlaybackTokenManager::class)->resolve())
        ->toBeInstanceOf(CloudFrontPlaybackTokenProvider::class);
});
