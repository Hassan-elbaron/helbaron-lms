<?php

use App\Contexts\Commerce\Enums\ProductStatus;
use App\Contexts\Commerce\Enums\SubscriptionStatus;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Models\Subscription;
use App\Contexts\Commerce\Models\SubscriptionPlan;
use App\Contexts\Commerce\Models\SubscriptionPlanPrice;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Models\Enrollment;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * `commerce:report-free-grants` — the read-only audit for decision (a).
 *
 * Two defects, both older than the branch that found them, wrote `source = free, expires_at = NULL`
 * onto courses that are sold. Both are fixed going forward; rows already in the database are not.
 * This command exists so the owner can see the real exposure before deciding anything, and it must
 * NEVER change a row — revoking a paying customer's access on data nobody has looked at would be a
 * far worse outcome than the leak itself.
 */
function freeGrant(User $user, Course $course, ?string $expiresAt = null): Enrollment
{
    return Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
        'source' => EnrollmentSource::Free->value,
        'enrolled_at' => now()->subMonth(),
        'expires_at' => $expiresAt,
    ]);
}

function soldCourse(string $status = ProductStatus::Active->value): Course
{
    $course = Course::factory()->published()->create();
    $product = Product::factory()->create(['status' => $status]);
    $product->courses()->sync([(int) $course->id]);

    return $course;
}

it('reports nothing when no free grant sits on a sold course', function (): void {
    $free = Course::factory()->published()->free()->create();
    freeGrant(User::factory()->create(), $free);

    $this->artisan('commerce:report-free-grants')
        ->expectsOutputToContain('No free-source enrolments found')
        ->assertSuccessful();
});

it('finds a perpetual free grant on a course sold by an active product', function (): void {
    $course = soldCourse();
    $user = User::factory()->create();
    freeGrant($user, $course);

    $this->artisan('commerce:report-free-grants')
        ->expectsOutputToContain('1 free-source enrolment(s) found')
        ->assertSuccessful();
});

it('finds a free grant on a course whose product is only a draft', function (): void {
    // The exact window the original leak opened in: an admin moved a live product to Draft to edit
    // pricing, and every course it sells became a one-click perpetual free enrolment.
    $course = soldCourse(ProductStatus::Draft->value);
    freeGrant(User::factory()->create(), $course);

    $this->artisan('commerce:report-free-grants')
        ->expectsOutputToContain('1 free-source enrolment(s) found')
        ->assertSuccessful();
});

/*
 * The column that turns the list into a decision.
 */
it('marks a holder with no current entitlement as LAPSED', function (): void {
    $course = soldCourse();
    freeGrant(User::factory()->create(), $course);

    $this->artisan('commerce:report-free-grants')
        ->expectsOutputToContain('1 of them are held by someone with NO current entitlement')
        ->assertSuccessful();
});

it('does not mark a holder as lapsed while their subscription still runs', function (): void {
    $course = Course::factory()->published()->create();
    $product = Product::factory()->create(['status' => ProductStatus::Active->value]);
    $product->courses()->sync([(int) $course->id]);

    $plan = SubscriptionPlan::create([
        'name' => 'All Access',
        'product_id' => $product->id,
        'interval' => 'monthly',
        'trial_days' => 0,
        'is_active' => true,
    ]);
    SubscriptionPlanPrice::create([
        'plan_id' => $plan->getKey(), 'currency' => 'SAR', 'amount_minor' => 9900, 'is_default' => true,
    ]);

    $user = User::factory()->create();
    Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->getKey(),
        'status' => SubscriptionStatus::Active->value,
        'current_period_start' => now()->subDay(),
        'current_period_end' => now()->addMonth(),
        'currency' => 'SAR',
        'amount_minor' => 9900,
        'provider' => 'fake',
    ]);

    freeGrant($user, $course);

    // Still a bad row — the grant is perpetual and the subscription is not — but it is LATENT, not a
    // live problem, and the report must say so rather than lumping it in with the lapsed ones.
    $this->artisan('commerce:report-free-grants')
        ->expectsOutputToContain('1 free-source enrolment(s) found')
        ->expectsOutputToContain('0 of them are held by someone with NO current entitlement')
        ->assertSuccessful();
});

it('changes nothing at all', function (): void {
    $course = soldCourse();
    $user = User::factory()->create();
    $enrollment = freeGrant($user, $course);

    $before = Enrollment::query()->get()->toArray();

    $this->artisan('commerce:report-free-grants')->assertSuccessful();

    // Read-only is the whole contract. If this ever starts revoking, it does so on a customer's
    // paid-for access, from a command whose name says "report".
    expect(Enrollment::query()->get()->toArray())->toBe($before)
        ->and($enrollment->fresh()->status->value)->toBe('active')
        ->and($enrollment->fresh()->source)->toBe(EnrollmentSource::Free)
        ->and(Enrollment::query()->count())->toBe(1);
});

it('writes every row to CSV when asked', function (): void {
    $course = soldCourse();
    freeGrant(User::factory()->create(), $course);
    freeGrant(User::factory()->create(), $course);

    $path = storage_path('app/free-grants-test.csv');
    @unlink($path);

    $this->artisan('commerce:report-free-grants', ['--csv' => $path])->assertSuccessful();

    expect(file_exists($path))->toBeTrue();

    $lines = array_filter(explode("\n", (string) file_get_contents($path)));

    // Header + one line per row.
    expect($lines)->toHaveCount(3)
        ->and($lines[0])->toContain('enrollment_id')
        ->and($lines[0])->toContain('entitlement');

    @unlink($path);
});

it('ignores enrolments that are not free-sourced', function (): void {
    $course = soldCourse();
    $user = User::factory()->create();

    Enrollment::create([
        'user_id' => $user->id,
        'course_id' => $course->id,
        'status' => 'active',
        'source' => EnrollmentSource::Purchase->value,
        'enrolled_at' => now(),
        'expires_at' => null,
    ]);

    // A purchase is perpetual by design — it is not what this report is looking for.
    $this->artisan('commerce:report-free-grants')
        ->expectsOutputToContain('No free-source enrolments found')
        ->assertSuccessful();
});
