<?php

use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);
});

it('stores media metadata only (no playback)', function () {
    $lesson = Lesson::factory()->ofType(LessonType::Video)->create();

    $res = $this->putJson("/api/v1/admin/lessons/{$lesson->public_id}/media", [
        'mux_asset_id' => 'asset_123',
        'mux_playback_id' => 'pb_123',
        's3_key' => 'media/x.mp4',
        'mime_type' => 'video/mp4',
        'duration' => 600,
        'filesize' => 1048576,
    ])->assertOk();

    expect($res->json('data.duration'))->toBe(600)
        ->and($res->json('data.mux_playback_id'))->toBe('pb_123');

    expect($lesson->media()->count())->toBe(1);
});
