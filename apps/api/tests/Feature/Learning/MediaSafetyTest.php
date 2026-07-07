<?php

use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Models\LessonMedia;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Contexts\Learning\Enums\EnrollmentSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/Helpers.php';

it('never exposes s3_key or mux_asset_id in the player', function () {
    [$course, $section, $lessons] = publishedCourseWithLessons(1);
    $lesson = $lessons->first();
    $lesson->update(['type' => LessonType::Video->value]);
    LessonMedia::factory()->create([
        'lesson_id' => $lesson->id,
        's3_key' => 'media/secret-key.mp4',
        'mux_asset_id' => 'asset_secret',
        'mux_playback_id' => 'pb_public',
    ]);

    $user = User::factory()->create();
    app(GrantEnrollmentAction::class)->execute($user, $course, EnrollmentSource::Free);
    Sanctum::actingAs($user);

    $res = $this->getJson("/api/v1/lessons/{$lesson->public_id}")->assertOk();

    expect($res->json('data.playback.url'))->toBeString();

    $raw = $res->getContent();
    expect($raw)->not->toContain('secret-key.mp4')
        ->and($raw)->not->toContain('asset_secret')
        ->and($raw)->not->toContain('s3_key')
        ->and($raw)->not->toContain('mux_asset_id');
});
