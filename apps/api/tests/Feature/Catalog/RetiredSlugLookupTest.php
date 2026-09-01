<?php

use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Repositories\PublicCourseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * D1, lookup half — a renamed course keeps serving its old URLs.
 *
 * The slug is the primary public URL for a course. Renaming one used to break every inbound link,
 * every share and every indexed search result at the same instant, with no way to recover because
 * the old string had been overwritten in place. The repository now falls back to `slug_history`, and
 * the page redirects permanently to the canonical slug (see the frontend course page).
 */
function publishedCourse(array $attributes = []): Course
{
    return Course::factory()->published()->create($attributes);
}

it('still resolves a course by a slug it used to have', function (): void {
    $course = publishedCourse(['title' => 'Python Basics', 'slug' => null]);
    $course->update(['slug' => 'python-fundamentals']);

    $found = app(PublicCourseRepository::class)->findByIdentifier('python-basics');

    expect($found)->not->toBeNull()
        ->and($found->getKey())->toBe($course->getKey())
        // The caller needs the canonical slug to redirect to; returning the record is not enough.
        ->and($found->slug)->toBe('python-fundamentals');
});

it('resolves the current slug without consulting history', function (): void {
    $course = publishedCourse(['title' => 'Python Basics', 'slug' => null]);

    $found = app(PublicCourseRepository::class)->findByIdentifier('python-basics');

    expect($found?->getKey())->toBe($course->getKey());
});

it('follows a chain of renames back to the original slug', function (): void {
    $course = publishedCourse(['title' => 'Python Basics', 'slug' => null]);
    $course->update(['slug' => 'python-fundamentals']);
    $course->update(['slug' => 'python-101']);

    // The first URL a course ever had is usually the one with the most links pointing at it.
    foreach (['python-basics', 'python-fundamentals', 'python-101'] as $slug) {
        expect(app(PublicCourseRepository::class)->findByIdentifier($slug)?->getKey())
            ->toBe($course->getKey());
    }
});

it('returns nothing for a slug no course has ever had', function (): void {
    publishedCourse(['title' => 'Python Basics', 'slug' => null]);

    expect(app(PublicCourseRepository::class)->findByIdentifier('never-existed'))->toBeNull();
});

/*
 * The negative that matters. History must not resurrect a course the public may no longer see: a
 * retired slug is a redirect, not a bypass of the published/visible scopes.
 */
it('does not expose an unpublished course through its old slug', function (): void {
    $course = publishedCourse(['title' => 'Python Basics', 'slug' => null]);
    $course->update(['slug' => 'python-fundamentals']);
    $course->update(['status' => 'draft']);

    expect(app(PublicCourseRepository::class)->findByIdentifier('python-basics'))->toBeNull();
});

it('still resolves a course by public id', function (): void {
    $course = publishedCourse(['title' => 'Python Basics', 'slug' => null]);

    expect(app(PublicCourseRepository::class)->findByIdentifier($course->public_id)?->getKey())
        ->toBe($course->getKey());
});
