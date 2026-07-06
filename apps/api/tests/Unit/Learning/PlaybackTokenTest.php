<?php

use App\Domains\Authoring\Models\LessonMedia;
use App\Domains\Learning\Playback\Providers\FakePlaybackTokenProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('issues a signed URL that never contains the raw storage key', function () {
    $media = LessonMedia::factory()->create([
        's3_key' => 'media/secret-key.mp4',
        'mux_asset_id' => 'asset_secret',
        'mux_playback_id' => 'pb_public',
    ]);

    $token = (new FakePlaybackTokenProvider)->issue($media, 600);

    expect($token->url)->toBeString()
        ->and($token->url)->not->toContain('secret-key.mp4')
        ->and($token->url)->not->toContain('asset_secret')
        ->and($token->expiresAt->isFuture())->toBeTrue();
});
