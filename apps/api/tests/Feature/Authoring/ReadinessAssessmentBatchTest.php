<?php

use App\Platform\Shared\Assessment\Contracts\LessonAssessmentPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * E4 — the readiness report resolved one assessment per quiz lesson.
 *
 * `describe()` is a query plus a `withCount` sub-select each, and the readiness report runs on every
 * publish attempt AND on every load of the instructor readiness panel. A twenty-quiz course spent
 * forty queries answering one question, and the author paid it every time they opened the page.
 *
 * Asserted by counting queries: "batched" is only true if the query count stops tracking the number
 * of quizzes.
 */
function countQueries(callable $work): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $work();

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('resolves many assessments in a bounded number of queries', function (): void {
    $port = app(LessonAssessmentPort::class);

    $few = countQueries(fn () => $port->describeMany([1, 2, 3]));
    $many = countQueries(fn () => $port->describeMany(range(1, 40)));

    // Thirteen times the ids must not mean thirteen times the queries.
    expect($many)->toBe($few);
});

it('answers the same thing describe() would, one id at a time', function (): void {
    $port = app(LessonAssessmentPort::class);

    // Unknown ids are simply absent, which is what reproduces describe()'s null for the caller and
    // keeps "a stale reference degrades to no quiz" true.
    expect($port->describeMany([987654, 987655]))->toBe([])
        ->and($port->describe(987654))->toBeNull();
});

it('ignores empty input rather than querying for nothing', function (): void {
    $port = app(LessonAssessmentPort::class);

    expect(countQueries(fn () => $port->describeMany([])))->toBe(0);
});

it('deduplicates repeated ids', function (): void {
    $port = app(LessonAssessmentPort::class);

    // Two lessons may legitimately point at the same assessment; that must not double the IN list.
    expect(countQueries(fn () => $port->describeMany([7, 7, 7, 7])))
        ->toBe(countQueries(fn () => $port->describeMany([7])));
});
