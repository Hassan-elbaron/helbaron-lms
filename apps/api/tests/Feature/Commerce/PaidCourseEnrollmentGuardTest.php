<?php

use App\Contexts\Commerce\Enums\ProductStatus;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Learning\Actions\Enrollment\EnrollInCourseAction;
use App\Contexts\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Exceptions\CoursePurchaseRequiredException;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Commerce\Contracts\EntitlementPort;
use App\Platform\Shared\Commerce\Data\CourseEntitlement;
use App\Platform\Shared\Commerce\Enums\EntitlementKind;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** A published course its author has declared free — the shape the payment-free path exists for. */
function enrollableCourse(bool $declaredFree = true): Course
{
    return Course::factory()->create([
        'status' => CourseStatus::Published->value,
        'published_at' => now()->subDay(),
        'is_free' => $declaredFree,
    ]);
}

function sellCourse(Course $course, string $status = ProductStatus::Active->value): Product
{
    $product = Product::factory()->create(['status' => $status]);
    $product->courses()->sync([(int) $course->id]);

    return $product;
}

it('refuses payment-free self-enrolment into a course that is sold', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse();
    sellCourse($course);

    app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);
})->throws(CoursePurchaseRequiredException::class);

it('still allows self-enrolment into a course nothing sells', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse();

    $enrollment = app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);

    expect($enrollment->exists)->toBeTrue()
        ->and((int) $enrollment->user_id)->toBe((int) $user->id);
});

/*
 * BEHAVIOUR CHANGE — this assertion is the exact inverse of what it was.
 *
 * It previously read "it does not lock a course away while its product is still a draft" and
 * asserted that the enrolment SUCCEEDED. The reasoning was that a course whose product is still
 * being prepared is not yet on sale, so it should not be locked away in the meantime.
 *
 * That reasoning does not survive contact with the grant it produces. The payment-free path calls
 * GrantEnrollmentAction with $expiresAt = null — a LIFETIME entitlement that no refund and no
 * revocation path undoes. And a product does not only sit in Draft before its first sale: an admin
 * moving a live product to Draft for five minutes to edit its pricing put every course that product
 * sells behind a one-click free front door for the duration, permanently, for anyone who clicked.
 *
 * It was unreachable through the UI while the frontend showed a disabled "not available yet"
 * button, which is why it survived review. It is reachable now, so the server refuses it. A course
 * that is not on sale yet is "not available", not "free" — those are different answers, and
 * PurchaseSummary now carries both.
 */
/*
 * The original revenue leak, preserved under the A5 model.
 *
 * A PAID course is not declared free (is_free defaults to false), so moving its product to Draft —
 * which an admin does for five minutes to edit pricing — must not open the payment-free path. This
 * previously granted a LIFETIME enrolment that no refund or revocation path undid.
 *
 * What changed in A5 is only which signal decides: the guard no longer infers freeness from product
 * rows, so it needs the course's own flag to be false rather than the product's status to be absent.
 * The protection is identical and the false-positive (see the declared-free draft case above) is gone.
 */
it('refuses payment-free self-enrolment into a paid course while its product is a draft', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse(declaredFree: false);
    sellCourse($course, ProductStatus::Draft->value);

    app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);
})->throws(CoursePurchaseRequiredException::class);

it('refuses payment-free self-enrolment into a paid course while its product is archived', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse(declaredFree: false);
    sellCourse($course, ProductStatus::Archived->value);

    app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);
})->throws(CoursePurchaseRequiredException::class);

it('keeps a declared-free course free even while a draft product also grants it', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse();
    sellCourse($course, ProductStatus::Draft->value);

    // A5: freeness is a STATED INTENT, so a draft product — an abandoned pricing experiment, or an
    // "All Access" bundle that happens to include this course — no longer un-frees it. Inferring
    // freeness from the absence of any product row made a single product_courses row a one-way door
    // whose only escape hatch (deleting the product) the admin panel does not expose at all.
    $enrollment = app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);

    expect($enrollment->exists)->toBeTrue()
        ->and($enrollment->source)->toBe(EnrollmentSource::Free);
});

it('refuses a course nobody declared free even when nothing sells it', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse(declaredFree: false);

    // Fail-closed: `courses.is_free` defaults to false, so a brand-new course is "not available yet"
    // rather than silently giveable-away. CourseReadinessService warns the author about exactly this.
    app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);
})->throws(CoursePurchaseRequiredException::class);

it('refuses a declared-free course while an ACTIVE product sells it', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse();
    sellCourse($course);

    // Belt and braces: an admin who ticks "free" on a course a live product sells has almost
    // certainly made a mistake, and checkout must win over the tick.
    app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);
})->throws(CoursePurchaseRequiredException::class);

it('lets a buyer who already holds the entitlement enrol without paying again', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse();
    sellCourse($course);

    // Stand in for a fulfilled purchase: the port reports the entitlement the order created.
    // The bypass is what lets a buyer whose order is already fulfilled use this endpoint, and
    // widening the guard from "actively sold" to "sold at all" must not take it away from them.
    $this->mock(EntitlementPort::class, function ($mock) {
        $mock->shouldReceive('isCourseFreeToEnroll')->andReturn(false);
        $mock->shouldReceive('isCourseSold')->andReturn(true);
        $mock->shouldReceive('courseEntitlement')
            ->andReturn(new CourseEntitlement(EntitlementKind::Purchase));
    });

    $enrollment = app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);

    // A purchase is the learner's own and perpetual — recorded as such, not as a free grant.
    expect($enrollment->exists)->toBeTrue()
        ->and($enrollment->source)->toBe(EnrollmentSource::Purchase)
        ->and($enrollment->expires_at)->toBeNull();
});

it('leaves the paid and company grant path untouched for a sold course', function (): void {
    $user = User::factory()->create();
    $course = enrollableCourse();
    sellCourse($course);

    // Order fulfilment and manager assignment both go straight to GrantEnrollmentAction, which the
    // purchase guard deliberately does not sit in front of.
    $enrollment = app(GrantEnrollmentAction::class)
        ->executeByUserId((int) $user->id, (int) $course->id, EnrollmentSource::Purchase);

    expect($enrollment->exists)->toBeTrue()
        ->and($enrollment->source)->toBe(EnrollmentSource::Purchase);
});

it('reports purchasability through the shared port', function (): void {
    $sold = enrollableCourse();
    $free = enrollableCourse();
    sellCourse($sold);

    $port = app(EntitlementPort::class);

    expect($port->isCoursePurchasable((int) $sold->id))->toBeTrue()
        ->and($port->isCoursePurchasable((int) $free->id))->toBeFalse();
});

it('reports soldness status-blind through the shared port', function (): void {
    $active = enrollableCourse();
    $draft = enrollableCourse();
    $archived = enrollableCourse();
    $unsold = enrollableCourse();

    sellCourse($active);
    sellCourse($draft, ProductStatus::Draft->value);
    sellCourse($archived, ProductStatus::Archived->value);

    $port = app(EntitlementPort::class);

    // isCoursePurchasable answers "on sale right now"; isCourseSold answers "spoken for at all".
    // The draft and archived cases are where they deliberately disagree.
    expect($port->isCourseSold((int) $active->id))->toBeTrue()
        ->and($port->isCourseSold((int) $draft->id))->toBeTrue()
        ->and($port->isCourseSold((int) $archived->id))->toBeTrue()
        ->and($port->isCourseSold((int) $unsold->id))->toBeFalse()
        ->and($port->isCoursePurchasable((int) $draft->id))->toBeFalse()
        ->and($port->isCoursePurchasable((int) $archived->id))->toBeFalse();
});
