<?php

use App\Domains\Authoring\Actions\Lesson\CreateLessonAction;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Models\Section;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('creates an audio lesson and round-trips the transcript via content', function () {
    $section = Section::factory()->create();

    $lesson = app(CreateLessonAction::class)->execute($section, [
        'title' => 'Intro (audio)',
        'type' => LessonType::Audio,
        'content' => ['transcript' => 'Welcome to the audio lesson.'],
    ]);

    expect($lesson->type)->toBe(LessonType::Audio)
        ->and($lesson->type->usesMedia())->toBeTrue()
        ->and($lesson->content['transcript'])->toBe('Welcome to the audio lesson.');
});

it('accepts the audio type through the admin create endpoint', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);

    $section = Section::factory()->create();

    $res = $this->postJson("/api/v1/admin/sections/{$section->public_id}/lessons", [
        'title' => 'Audio lesson',
        'type' => 'audio',
        'content' => ['transcript' => 'Transcript body.'],
    ])->assertCreated();

    expect($res->json('data.type'))->toBe('audio')
        ->and($res->json('data.content.transcript'))->toBe('Transcript body.');
});
