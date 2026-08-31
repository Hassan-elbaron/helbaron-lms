<?php

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * D1 — HasSlug. Four defects, every one of them reachable from the admin panel by typing a title.
 *
 * The auto-fill exists precisely for the case where an author leaves the slug blank, and that was
 * the case it handled worst: no uniqueness check, no result at all for a non-Latin title, and a
 * translated title it could not see. All three ended as a raw SQLSTATE 500 or a silently missing
 * slug rather than a validation message.
 */
function makeCourse(array $attributes = []): Course
{
    return Course::factory()->create($attributes);
}

/*
 * Defect 1. The trait called Slug::make() rather than the Slug::unique() helper sitting ten lines
 * below it in the same file. Filament's ->unique(ignoreRecord: true) does not cover this, because it
 * only validates a SUBMITTED value and the whole point of the auto-fill is that none was submitted.
 */
it('does not collide when two records share a title', function (): void {
    $first = makeCourse(['title' => 'Python Basics', 'slug' => null]);
    $second = makeCourse(['title' => 'Python Basics', 'slug' => null]);

    expect($first->slug)->toBe('python-basics')
        ->and($second->slug)->toBe('python-basics-2')
        ->and($second->slug)->not->toBe($first->slug);
});

it('keeps suffixing past the second collision', function (): void {
    $slugs = collect(range(1, 4))
        ->map(fn (): string => (string) makeCourse(['title' => 'Data Science', 'slug' => null])->slug);

    expect($slugs->unique())->toHaveCount(4)
        ->and($slugs->all())->toBe(['data-science', 'data-science-2', 'data-science-3', 'data-science-4']);
});

/*
 * Defect 2. Str::slug($value, '-', 'en') returns '' for a title with no ASCII mapping. '' then went
 * into a NOT NULL UNIQUE column, so the FIRST such course saved and every one after it produced a
 * raw 500.
 *
 * MEASURED, because the brief named Arabic and Arabic is not actually affected: Laravel
 * transliterates it (`أساسيات البرمجة` -> `asasyat-albrmg`), as it does Greek and Cyrillic. What
 * really yields '' is CJK, Korean, emoji, and punctuation-only titles — see the dataset. Narrower
 * than advertised, and still a 500 on the second course either way.
 */
it('falls back to the public id when a title yields no ascii', function (string $title): void {
    $course = makeCourse(['title' => $title, 'slug' => null]);

    expect($course->slug)->not->toBe('')
        ->and($course->slug)->toBe($course->public_id);
})->with([
    'japanese' => ['日本語コース'],
    'chinese' => ['课程'],
    'korean' => ['한국어'],
    'emoji' => ['🎓🎓'],
    'punctuation only' => ['!!! ???'],
]);

it('transliterates rather than falling back where it can', function (string $title, string $expected): void {
    // The fallback is a last resort, not the first answer: a readable slug beats a UUID whenever
    // one can be produced.
    expect(makeCourse(['title' => $title, 'slug' => null])->slug)->toBe($expected);
})->with([
    'arabic' => ['أساسيات البرمجة', 'asasyat-albrmg'],
    'greek' => ['Ελληνικά', 'ellinika'],
    'cyrillic' => ['Русский', 'russkii'],
]);

it('saves more than one course whose title yields no ascii', function (): void {
    $first = makeCourse(['title' => '日本語コース', 'slug' => null]);
    $second = makeCourse(['title' => '日本語コース', 'slug' => null]);

    // The defect: both resolved to '' and the second one hit the unique index as a raw 500.
    expect($first->slug)->not->toBe($second->slug)
        ->and(Course::query()->whereIn('slug', [$first->slug, $second->slug])->count())->toBe(2);
});

/*
 * Defect 3. `$translations[$default] ?? reset($translations)` does not fire when the `en` key EXISTS
 * and is empty — the shape a form submitting every locale field always sends. The Arabic title was
 * therefore never reached and no slug was generated at all.
 */
it('uses a translated title when the default locale is present but empty', function (): void {
    $course = makeCourse([
        'title' => '',
        'slug' => null,
        'title_i18n' => ['en' => '', 'ar' => 'البرمجة'],
    ]);

    expect($course->slug)->not->toBe('');
});

it('prefers the default locale when it has content', function (): void {
    $course = makeCourse([
        'title' => '',
        'slug' => null,
        'title_i18n' => ['en' => 'Machine Learning', 'ar' => 'تعلم الآلة'],
    ]);

    expect($course->slug)->toBe('machine-learning');
});

/*
 * Slug history. A course slug is the primary public URL; renaming one used to 404 every inbound
 * link, share and indexed result at once, with no way back because the old string was overwritten.
 */
it('remembers a slug it stops using', function (): void {
    $course = makeCourse(['title' => 'Python Basics', 'slug' => null]);

    $course->update(['slug' => 'python-fundamentals']);

    expect(DB::table('slug_history')->where('slug', 'python-basics')->count())->toBe(1)
        ->and($course->fresh()->slug)->toBe('python-fundamentals');
});

/*
 * The subtle half, and the reason history participates in the uniqueness check. If a retired slug
 * could be reissued, every historic link for the ORIGINAL course would resolve to a DIFFERENT
 * course — a wrong page served with a 200, which is worse than the 404 the redirect replaced.
 */
it('never reissues a retired slug to another record', function (): void {
    $original = makeCourse(['title' => 'Python Basics', 'slug' => null]);
    $original->update(['slug' => 'python-fundamentals']);

    $newcomer = makeCourse(['title' => 'Python Basics', 'slug' => null]);

    expect($newcomer->slug)->not->toBe('python-basics')
        ->and($newcomer->slug)->toBe('python-basics-2');
});

it('lets a record take back its own retired slug', function (): void {
    $course = makeCourse(['title' => 'Python Basics', 'slug' => null]);
    $course->update(['slug' => 'python-fundamentals']);

    // Its own history must not lock a record out of a name it previously held.
    $course->update(['slug' => 'python-basics']);

    expect($course->fresh()->slug)->toBe('python-basics');
});

it('records no history when the slug does not change', function (): void {
    $course = makeCourse(['title' => 'Python Basics', 'slug' => null]);

    $course->update(['title' => 'Python Basics Revised']);

    expect(DB::table('slug_history')->count())->toBe(0);
});

/*
 * History is scoped per model type. Two different kinds of record may each have owned `design`;
 * locking that globally would exhaust obvious names across unrelated parts of the catalogue.
 */
it('scopes history to the model type', function (): void {
    $course = makeCourse(['title' => 'Design', 'slug' => null]);
    $course->update(['slug' => 'design-thinking']);

    $category = Category::factory()->create(['name' => 'Design', 'slug' => null]);

    expect($category->slug)->toBe('design');
});

it('leaves an explicitly supplied slug alone when it is free', function (): void {
    $course = makeCourse(['title' => 'Python Basics', 'slug' => 'chosen-by-hand']);

    expect($course->slug)->toBe('chosen-by-hand');
});

it('makes an explicitly supplied slug unique rather than failing', function (): void {
    makeCourse(['title' => 'One', 'slug' => 'taken']);
    $second = makeCourse(['title' => 'Two', 'slug' => 'taken']);

    expect($second->slug)->toBe('taken-2');
});

it('does not rewrite the slug of a record that is merely re-saved', function (): void {
    $course = makeCourse(['title' => 'Python Basics', 'slug' => null]);

    $course->update(['title' => 'Something Else Entirely']);

    // The slug is the public URL. Editing a title must not silently move the page.
    expect($course->fresh()->slug)->toBe('python-basics');
});

/*
 * Defect 4, and the only one that cannot be proven by calling the code normally.
 *
 * Between the SELECT that checks a candidate slug and the INSERT that uses it, another request can
 * take that slug. No amount of checking closes that window — the unique index is the only real
 * authority — so the trait catches a 23505 that names the slug column and retries.
 *
 * The race is simulated deterministically: a listener registered AFTER the trait's own `saving` hook
 * inserts the conflicting row once, in the same moment a competing request would have. The first
 * INSERT then genuinely violates the index.
 */
it('recovers when another writer takes the slug first', function (): void {
    $thrown = false;

    // Boot the model FIRST. Eloquent registers a trait's hooks during boot, and boot is lazy — a
    // listener registered before it would run BEFORE HasSlug had chosen a slug.
    new Course;

    // The violation is INJECTED rather than raced for. Two earlier attempts at this test failed for
    // instructive reasons, both recorded here because they are the reason this shape was chosen:
    //   1. Inserting the competing row through a second query on the same connection put it inside
    //      the same transaction, so the savepoint rollback that makes the retry work also undid the
    //      competitor and the slug was simply free again.
    //   2. A genuinely concurrent writer needs a second committed connection, which RefreshDatabase's
    //      wrapping transaction rules out.
    // Throwing the exact exception PostgreSQL raises exercises the whole recovery path — the
    // constraint-name check, the savepoint rollback, and the fresh uniqueness pass — without needing
    // two connections.
    Course::saving(function (Course $course) use (&$thrown): void {
        if ($thrown || $course->exists) {
            return;
        }

        $thrown = true;

        throw new QueryException(
            'pgsql',
            'insert into "courses" ...',
            [],
            new PDOException(
                'SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique '
                .'constraint "courses_slug_unique"',
                23505,
            ),
        );
    });

    $course = Course::factory()->create(['title' => 'Race Loser', 'slug' => null]);

    // What this proves: the violation was raised, it did NOT escape, and the record persisted. That
    // is the whole of the recovery — the transaction survived the failed attempt (a savepoint
    // rollback) and the second attempt committed.
    //
    // The slug is still `race-loser`, and that is correct rather than a miss: the retry re-runs the
    // uniqueness check, and because this simulation raises the error without committing a competing
    // row, the slug genuinely IS free the second time. Against a real competitor the same
    // re-check yields the next suffix — which is what the collision tests at the top of this file
    // assert directly.
    expect($thrown)->toBeTrue()
        ->and($course->exists)->toBeTrue()
        ->and($course->slug)->toBe('race-loser')
        ->and(Course::query()->where('slug', 'race-loser')->count())->toBe(1);
});

it('does not swallow a unique violation from an unrelated column', function (): void {
    $first = makeCourse(['title' => 'Distinct One', 'slug' => null]);

    // public_id also carries a unique index. A 23505 naming it is a real error and must surface,
    // not be retried as though the slug were the problem — the retry would loop and then rethrow a
    // confusing error three attempts later.
    $second = Course::factory()->make(['title' => 'Distinct Two', 'slug' => null]);
    $second->public_id = $first->public_id;

    expect(fn () => $second->save())->toThrow(QueryException::class);
});
