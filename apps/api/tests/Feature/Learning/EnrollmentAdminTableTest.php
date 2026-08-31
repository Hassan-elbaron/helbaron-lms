<?php

use App\Contexts\Learning\Filament\Resources\EnrollmentResource;
use App\Contexts\Learning\Models\Enrollment;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Contracts\UserLookupPort;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

/**
 * D2 — the enrollment admin table. Three defects, all introduced by the change that moved the
 * Learner column behind UserLookupPort.
 */
beforeEach(function (): void {
    // The four protected system roles the panel authorises against.
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(SpatieRole::findByName('super_admin', 'web'));

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
});

function enrollmentsFor(int $count): void
{
    $course = Course::factory()->published()->create(['title' => 'Python Basics']);

    foreach (range(1, $count) as $i) {
        Enrollment::factory()->create([
            'user_id' => User::factory()->create(['name' => "Learner {$i}"])->id,
            'course_id' => $course->id,
        ]);
    }
}

/*
 * Defect 1, and the reason it matters: refById() is TWO queries (user + profile) and Filament
 * re-renders the whole table on every sort, filter, search keystroke and page change. Twenty rows
 * cost forty queries per interaction. The column this replaced (`user.email`) had been eager-loaded
 * automatically, so this was a regression shipped with the port, not old debt.
 *
 * Asserted by counting queries rather than by reading the code, because "batched" is only true if
 * the query count stops tracking the row count.
 */
it('resolves learner names in a bounded number of queries', function (): void {
    enrollmentsFor(20);

    DB::enableQueryLog();
    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)->assertOk();
    $manyRows = count(DB::getQueryLog());
    DB::flushQueryLog();

    Enrollment::query()->limit(10)->get()->each->delete();

    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)->assertOk();
    $fewRows = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Halving the rows must not halve the queries. Under the per-row lookup it did exactly that.
    expect($manyRows - $fewRows)->toBeLessThan(10);
});

it('still shows the learner name', function (): void {
    enrollmentsFor(1);

    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)
        ->assertSee('Learner 1');
});

it('says so plainly when a learner cannot be resolved', function (): void {
    enrollmentsFor(1);

    // A soft-deleted learner, not a hard delete: enrollments.user_id is cascadeOnDelete, so removing
    // the row would take the enrollment with it and there would be no cell left to assert on. The
    // port's queries are soft-delete scoped, so the ref stops resolving while the enrollment stays —
    // which is the state an admin actually meets after deactivating an account.
    User::query()->where('name', 'Learner 1')->delete();

    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)
        ->assertSee('Unknown learner');
});

/*
 * Defect 2. `TextColumn::make('course.title')` renders the RAW scalar. For an i18n-only course —
 * exactly the case the column was changed to support — that scalar is empty, so the cell an admin
 * needs stayed blank. Every other surface reads it through localized().
 */
it('renders a translated course title rather than a blank cell', function (): void {
    $course = Course::factory()->published()->create([
        'title' => '',
        'title_i18n' => ['en' => 'Localized Course Title', 'ar' => 'عنوان'],
    ]);
    Enrollment::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Learner X'])->id,
        'course_id' => $course->id,
    ]);

    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)
        ->assertSee('Localized Course Title');
});

/*
 * Defect 3. The Learner column lost ->searchable(), so an admin could no longer find an enrollment
 * by the learner's email — the single most common way anyone arrives at this screen (a support
 * ticket names an email, not a course).
 */
it('finds an enrollment by the learner email', function (): void {
    $course = Course::factory()->published()->create(['title' => 'Python Basics']);
    $wanted = User::factory()->create(['name' => 'Wanted Learner', 'email' => 'wanted@example.test']);
    $other = User::factory()->create(['name' => 'Other Learner', 'email' => 'other@example.test']);

    Enrollment::factory()->create(['user_id' => $wanted->id, 'course_id' => $course->id]);
    Enrollment::factory()->create(['user_id' => $other->id, 'course_id' => $course->id]);

    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)
        ->searchTable('wanted@example.test')
        ->assertSee('Wanted Learner')
        ->assertDontSee('Other Learner');
});

it('finds an enrollment by the learner name', function (): void {
    $course = Course::factory()->published()->create(['title' => 'Python Basics']);
    $wanted = User::factory()->create(['name' => 'Zebediah Unique', 'email' => 'z@example.test']);
    $other = User::factory()->create(['name' => 'Other Learner', 'email' => 'other@example.test']);

    Enrollment::factory()->create(['user_id' => $wanted->id, 'course_id' => $course->id]);
    Enrollment::factory()->create(['user_id' => $other->id, 'course_id' => $course->id]);

    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)
        ->searchTable('Zebediah')
        ->assertSee('Zebediah Unique')
        ->assertDontSee('Other Learner');
});

/*
 * A search that matches nobody must return NOTHING. An empty id list fed to whereIn is already
 * false in SQL, but the failure mode if it were ever skipped is the opposite of a filter: every
 * enrollment in the system on one screen.
 */
it('returns no rows when no learner matches', function (): void {
    enrollmentsFor(3);

    Livewire::test(EnrollmentResource\Pages\ListEnrollments::class)
        ->searchTable('nobody-by-this-name@nowhere.test')
        ->assertDontSee('Learner 1')
        ->assertDontSee('Learner 2');
});

it('treats LIKE wildcards in a search term as literal characters', function (): void {
    $course = Course::factory()->published()->create(['title' => 'Python Basics']);
    Enrollment::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Percent Free', 'email' => 'p@example.test'])->id,
        'course_id' => $course->id,
    ]);

    // Unescaped, '%' would match every user and turn a search into "show everything".
    expect(app(UserLookupPort::class)->idsMatching('%'))->toBe([]);
});
