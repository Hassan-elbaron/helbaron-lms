<?php

use App\Platform\Media\Playback\Providers\FakePlaybackSigner;
use App\Platform\Shared\Media\Data\MediaAccessPolicy;
use App\Platform\Shared\Media\Data\MediaAssetRef;

it('issues a signed URL that never contains the raw storage key', function () {
    $asset = new MediaAssetRef(
        id: 'lm_public',
        provider: 'mux',
        playbackId: 'pb_public',
        storageKey: 'media/secret-key.mp4',
        mimeType: 'video/mp4',
        durationSeconds: 60,
        policy: new MediaAccessPolicy(signed: true, visibility: 'private'),
        metadata: [],
    );

    $token = (new FakePlaybackSigner)->issue($asset, 600);

    expect($token->url)->toBeString()
        ->and($token->url)->not->toContain('secret-key.mp4')
        ->and($token->url)->not->toContain('asset_secret')
        ->and($token->expiresAt->isFuture())->toBeTrue();
});
