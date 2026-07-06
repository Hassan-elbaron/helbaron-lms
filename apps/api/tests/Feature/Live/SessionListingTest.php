<?php

use App\Domains\Live\Models\LiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists upcoming sessions and hides the raw join URL in detail', function () {
    $upcoming = LiveSession::factory()->create(['title' => 'Future']);
    $upcoming->forceFill(['join_url' => 'https://meet.fake.local/secret'])->save();
    LiveSession::factory()->cancelled()->create(['title' => 'Cancelled']);

    $list = $this->getJson('/api/v1/live-sessions')->assertOk();
    expect(collect($list->json('data'))->pluck('title'))->toContain('Future')
        ->and(collect($list->json('data'))->pluck('title'))->not->toContain('Cancelled');

    $detail = $this->getJson("/api/v1/live-sessions/{$upcoming->public_id}")->assertOk();
    expect($detail->getContent())->not->toContain('join_url')->not->toContain('meet.fake.local');
});
