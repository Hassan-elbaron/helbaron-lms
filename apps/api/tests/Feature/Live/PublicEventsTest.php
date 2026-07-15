<?php

use App\Domains\Catalog\Models\Course;
use App\Domains\Live\Models\LiveCourse;
use App\Domains\Live\Models\LiveSession;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists only upcoming public events and excludes past and cancelled', function () {
    LiveSession::factory()->create(['title' => 'Upcoming Summit']);
    LiveSession::factory()->completed()->create(['title' => 'Past Recap']);
    LiveSession::factory()->cancelled()->create(['title' => 'Called Off']);

    $res = $this->getJson('/api/v1/events?filter=upcoming')->assertOk();
    $titles = collect($res->json('data'))->pluck('title');

    expect($titles)->toContain('Upcoming Summit')
        ->and($titles)->not->toContain('Past Recap')
        ->and($titles)->not->toContain('Called Off');
});

it('lists past events under the past filter', function () {
    LiveSession::factory()->create(['title' => 'Upcoming Summit']);
    LiveSession::factory()->completed()->create(['title' => 'Past Recap']);

    $res = $this->getJson('/api/v1/events?filter=past')->assertOk();
    $titles = collect($res->json('data'))->pluck('title');

    expect($titles)->toContain('Past Recap')
        ->and($titles)->not->toContain('Upcoming Summit');
});

it('filters events by a search query over the title', function () {
    LiveSession::factory()->create(['title' => 'Leadership Masterclass']);
    LiveSession::factory()->create(['title' => 'Data Engineering Bootcamp']);

    $res = $this->getJson('/api/v1/events?filter=upcoming&q=Leadership')->assertOk();
    $titles = collect($res->json('data'))->pluck('title');

    expect($titles)->toContain('Leadership Masterclass')
        ->and($titles)->not->toContain('Data Engineering Bootcamp');
});

it('returns detail with speakers, related course and counts and never exposes the join URL', function () {
    $course = Course::factory()->create(['title' => 'Advanced Analytics']);
    $liveCourse = LiveCourse::factory()->create(['course_id' => $course->id]);
    $session = LiveSession::factory()->create(['live_course_id' => $liveCourse->id]);
    $session->forceFill(['join_url' => 'https://meet.fake.local/secret-room'])->save();

    $speaker = User::factory()->create(['name' => 'Dr Ada Speaker']);
    $session->syncTrainers([$speaker->id]);

    // One registered attendee for the counts.
    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/events/{$session->public_id}/register")->assertCreated();

    $res = $this->getJson("/api/v1/events/{$session->public_id}")->assertOk();

    $res->assertJsonPath('data.related_course.title', 'Advanced Analytics')
        ->assertJsonPath('data.related_course.public_id', $course->public_id)
        ->assertJsonPath('data.registered_count', 1)
        ->assertJsonPath('data.speakers.0.name', 'Dr Ada Speaker');

    expect($res->getContent())->not->toContain('join_url')
        ->not->toContain('meet.fake.local');
});

it('registers an authenticated user and waitlists beyond capacity via the existing action', function () {
    $session = LiveSession::factory()->capacity(1)->create();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/events/{$session->public_id}/register")
        ->assertCreated()->assertJsonPath('data.status', 'registered');

    Sanctum::actingAs(User::factory()->create());
    $this->postJson("/api/v1/events/{$session->public_id}/register")
        ->assertCreated()->assertJsonPath('data.status', 'waitlisted');
});

it('rejects anonymous registration with 401', function () {
    $session = LiveSession::factory()->create();

    $this->postJson("/api/v1/events/{$session->public_id}/register")->assertUnauthorized();
});
