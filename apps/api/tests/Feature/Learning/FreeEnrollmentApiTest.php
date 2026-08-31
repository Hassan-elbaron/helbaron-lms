<?php

use App\Contexts\Commerce\Enums\ProductStatus;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Learning\Models\Enrollment;
use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);
require_once __DIR__.'/Helpers.php';

/**
 * The payment-free enrolment path, exercised THROUGH THE HTTP API rather than through the action.
 *
 * There was no API-level coverage of this at all: the frontend test mocked the happy path and the
 * unit-level guard test called the action directly, so nothing asserted what a real client actually
 * receives. That is the layer the revenue leak lived at — the UI read `purchasable !== true` and
 * offered a free-enrol button, and the endpoint behind it agreed.
 *
 * Two things are asserted together on purpose: the enrolment endpoint's verdict, and the `free`
 * flag the course endpoint publishes. They have to agree, because the button the learner sees is
 * rendered from the second and enforced by the first.
 */
function sellCourseApi(Course $course, string $status = ProductStatus::Active->value): Product
{
    $product = Product::factory()->create(['status' => $status]);
    $product->courses()->sync([(int) $course->id]);

    return $product;
}

/**
 * Mark a course as NOT declared free — the state every paid course is in.
 *
 * `courses.is_free` defaults to false, so this is what a course being sold actually looks like.
 * publishedCourseWithLessons() declares its course free (it stands for "an ordinary enrollable
 * course"), so the paid cases below have to say otherwise explicitly.
 */
function markAsPaid(Course $course): Course
{
    $course->forceFill(['is_free' => false])->save();

    return $course->refresh();
}

it('grants payment-free enrolment for a course no product sells', function (): void {
    [$course] = publishedCourseWithLessons(1);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")->assertCreated();
});

it('refuses payment-free enrolment for a course sold by an active product', function (): void {
    [$course] = publishedCourseWithLessons(1);
    sellCourseApi($course);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")
        ->assertStatus(402)
        ->assertJsonPath('error.code', 'LEARNING_COURSE_PURCHASE_REQUIRED');

    // The status code alone is not the guarantee. A path that created the enrollment and THEN
    // returned 402 would keep every assertion above green while the revenue leak persisted.
    $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id]);
});

it('refuses payment-free enrolment for a paid course whose product is a draft', function (): void {
    [$course] = publishedCourseWithLessons(1);
    markAsPaid($course);
    sellCourseApi($course, ProductStatus::Draft->value);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")
        ->assertStatus(402)
        ->assertJsonPath('error.code', 'LEARNING_COURSE_PURCHASE_REQUIRED');

    // The status code alone is not the guarantee. A path that created the enrollment and THEN
    // returned 402 would keep every assertion above green while the revenue leak persisted.
    $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id]);
});

it('refuses payment-free enrolment for a paid course whose product is archived', function (): void {
    [$course] = publishedCourseWithLessons(1);
    markAsPaid($course);
    sellCourseApi($course, ProductStatus::Archived->value);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")
        ->assertStatus(402)
        ->assertJsonPath('error.code', 'LEARNING_COURSE_PURCHASE_REQUIRED');

    // The status code alone is not the guarantee. A path that created the enrollment and THEN
    // returned 402 would keep every assertion above green while the revenue leak persisted.
    $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id]);
});

it('publishes free=true on the course endpoint only when nothing sells it', function (): void {
    [$course] = publishedCourseWithLessons(1);

    $this->getJson("/api/v1/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.purchase.purchasable', false)
        ->assertJsonPath('data.purchase.free', true);
});

it('publishes free=false for a paid draft-product course so the UI cannot offer it for nothing', function (): void {
    [$course] = publishedCourseWithLessons(1);
    markAsPaid($course);
    sellCourseApi($course, ProductStatus::Draft->value);

    // The regression in one line: purchasable is false here, exactly as it is for a genuinely free
    // course. Anything deriving freeness from `! purchasable` gives this course away.
    $this->getJson("/api/v1/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.purchase.purchasable', false)
        ->assertJsonPath('data.purchase.free', false);
});

it('publishes free=false for a paid archived-product course', function (): void {
    [$course] = publishedCourseWithLessons(1);
    markAsPaid($course);
    sellCourseApi($course, ProductStatus::Archived->value);

    $this->getJson("/api/v1/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.purchase.purchasable', false)
        ->assertJsonPath('data.purchase.free', false);
});

it('publishes free=false alongside purchasable=true for an actively sold course', function (): void {
    [$course] = publishedCourseWithLessons(1);
    sellCourseApi($course);

    $this->getJson("/api/v1/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.purchase.purchasable', true)
        ->assertJsonPath('data.purchase.free', false);
});

it('keeps the course listing free flag consistent with the detail endpoint', function (): void {
    [$free] = publishedCourseWithLessons(1);
    [$draft] = publishedCourseWithLessons(1);
    markAsPaid($draft);
    sellCourseApi($draft, ProductStatus::Draft->value);

    $body = $this->getJson('/api/v1/courses')->assertOk()->json('data');

    $byId = collect($body)->keyBy('id');

    expect($byId[$free->public_id]['purchase']['free'])->toBeTrue()
        ->and($byId[$draft->public_id]['purchase']['free'])->toBeFalse();
});

/*
 * A5's third state, end to end: nothing sells the course, but nobody declared it free either.
 * `courses.is_free` is fail-closed, so this is what a brand-new course looks like.
 */
it('refuses enrolment and reports not-free for a course nobody declared free', function (): void {
    [$course] = publishedCourseWithLessons(1);
    markAsPaid($course);
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")
        ->assertStatus(402)
        ->assertJsonPath('error.code', 'LEARNING_COURSE_PURCHASE_REQUIRED');

    $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id]);

    $this->getJson("/api/v1/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.purchase.purchasable', false)
        ->assertJsonPath('data.purchase.free', false);
});

it('keeps a declared-free course free even when a draft product also grants it', function (): void {
    [$course] = publishedCourseWithLessons(1);
    sellCourseApi($course, ProductStatus::Draft->value);
    Sanctum::actingAs(User::factory()->create());

    // The A5 fix: a draft product (an abandoned pricing experiment, or an "All Access" bundle that
    // happens to include this course) no longer un-frees a course its author declared free.
    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")->assertCreated();

    $this->getJson("/api/v1/courses/{$course->public_id}")
        ->assertOk()
        ->assertJsonPath('data.purchase.free', true);
});

it('refuses enrolment to an anonymous caller and writes nothing', function (): void {
    [$course] = publishedCourseWithLessons(1);

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")->assertUnauthorized();

    $this->assertDatabaseMissing('enrollments', ['course_id' => $course->id]);
});

it('is idempotent when the same learner enrols twice', function (): void {
    [$course] = publishedCourseWithLessons(1);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")->assertCreated();
    $this->postJson("/api/v1/courses/{$course->public_id}/enroll")->assertCreated();

    // One learner, one course, one row — a second POST must not duplicate the enrolment.
    expect(Enrollment::query()
        ->where('user_id', $user->id)
        ->where('course_id', $course->id)
        ->count())->toBe(1);
});

/*
 * Related-course cards must carry their own purchase summary.
 *
 * PublicCourseDetailsService set the `related` relation but attached a summary only to the MAIN
 * course. The card renders from that summary, and the client default is fail-closed, so every
 * cross-sell card read "Not available yet" — including courses that were perfectly buyable.
 */
it('gives every related course its own purchase summary', function (): void {
    $category = Category::factory()->create();

    [$course] = publishedCourseWithLessons(1);
    $course->categories()->sync([$category->id]);

    $relatedFree = Course::factory()->published()->free()->create();
    $relatedFree->categories()->sync([$category->id]);

    $relatedSold = Course::factory()->published()->create();
    $relatedSold->categories()->sync([$category->id]);
    sellCourseApi($relatedSold);

    $body = $this->getJson("/api/v1/courses/{$course->public_id}")->assertOk()->json('data');

    $cards = collect($body['related'])->keyBy('id');

    expect($cards)->toHaveCount(2)
        // The free one advertises itself as free...
        ->and($cards[$relatedFree->public_id]['purchase']['free'])->toBeTrue()
        // ...and the sold one as buyable, instead of both falling back to "not available yet".
        ->and($cards[$relatedSold->public_id]['purchase']['purchasable'])->toBeTrue()
        ->and($cards[$relatedSold->public_id]['purchase']['free'])->toBeFalse();
});
