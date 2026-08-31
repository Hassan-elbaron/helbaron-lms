<?php

use App\Contexts\Commerce\Actions\Subscription\SubscribeOrganizationAction;
use App\Contexts\Commerce\Contracts\PaymentGateway;
use App\Contexts\Commerce\Enums\OrderStatus;
use App\Contexts\Commerce\Enums\SubscriptionStatus;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Models\OrderCourseGrant;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Models\Subscription;
use App\Contexts\Commerce\Models\SubscriptionPlan;
use App\Contexts\Commerce\Models\SubscriptionPlanPrice;
use App\Contexts\Commerce\Payments\Data\ChargeRequest;
use App\Contexts\Commerce\Payments\Data\ChargeResult;
use App\Contexts\Commerce\Payments\Data\RefundRequest;
use App\Contexts\Commerce\Payments\Data\RefundResult;
use App\Contexts\Commerce\Payments\Data\WebhookEvent;
use App\Contexts\Commerce\Services\OrganizationSubscriptionService;
use App\Contexts\Learning\Actions\Enrollment\EnrollInCourseAction;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Exceptions\CoursePurchaseRequiredException;
use App\Domains\Catalog\Models\Course;
use App\Domains\Crm\Models\Organization;
use App\Domains\Crm\Models\OrganizationMember;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Audit\AuditLogger;
use App\Platform\Shared\Learning\Contracts\CourseEnrollmentPort;
use App\Platform\Shared\Seats\Contracts\SeatProvisioningPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * A4 — an entitlement must never be re-sourced as a perpetual free grant.
 *
 * The payment-free enrol endpoint lets an already-entitled learner through, which is correct: a
 * buyer whose order is fulfilled must be able to use it. What was wrong is what it WROTE. Every
 * entitled caller fell through to `GrantEnrollmentAction(..., EnrollmentSource::Free)` with the
 * default `$expiresAt = null`, and access is then decided by that enrollment row alone —
 * CourseEnrollmentAdapter is `grantsAccess()->notExpired()->exists()` and never re-consults the
 * entitlement port.
 *
 * Three of the four entitlement sources are revocable or time-boxed, and there was no
 * `EnrollmentSource::Subscription` at all, so a subscriber had no enrollment row until they pressed
 * "Enroll" — which is the intended path for them. One POST per course therefore converted a monthly
 * subscription into permanent access to the entire bundled catalogue. Nothing reclaimed it:
 * RevokeEnrollmentsOnRefund needs an order, and CompanySeatEnrollmentAdapter::revokeCompanySeat()
 * filters on `source = company_seat`.
 */
function a4Gateway(): PaymentGateway
{
    return new class implements PaymentGateway
    {
        public function charge(ChargeRequest $request): ChargeResult
        {
            return new ChargeResult('prov_'.($request->idempotencyKey ?? $request->reference), 'succeeded');
        }

        public function refund(RefundRequest $request): RefundResult
        {
            return new RefundResult($request->providerReference, 'succeeded');
        }

        public function parseWebhook(string $payload, ?string $signature): WebhookEvent
        {
            return new WebhookEvent('evt', 'payment.succeeded', 'ref');
        }
    };
}

/** A plan bundling one published, sold course through a product. Returns [plan, course]. */
function a4PlanWithCourse(): array
{
    $course = Course::factory()->published()->create();
    $product = Product::factory()->create();
    $product->courses()->sync([$course->id]);

    $plan = SubscriptionPlan::create([
        'name' => 'All Access',
        'product_id' => $product->id,
        'interval' => 'monthly',
        'trial_days' => 0,
        'is_active' => true,
    ]);
    SubscriptionPlanPrice::create([
        'plan_id' => $plan->getKey(),
        'currency' => 'SAR',
        'amount_minor' => 9900,
        'is_default' => true,
    ]);

    return [$plan->load('prices'), $course];
}

function a4Enroll(User $user, Course $course)
{
    return app(EnrollInCourseAction::class)->executeByUserId((int) $user->id, (int) $course->id);
}

function a4HasAccess(User $user, Course $course): bool
{
    return app(CourseEnrollmentPort::class)->hasCourseAccess((int) $course->id, (int) $user->id);
}

// -- Individual subscription -----------------------------------------------------------------------

it('records a subscriber enrolment as a subscription with a real expiry, not a free perpetual grant', function (): void {
    [$plan, $course] = a4PlanWithCourse();
    $user = User::factory()->create();

    Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->getKey(),
        'status' => SubscriptionStatus::Active->value,
        'current_period_start' => now()->subDays(3),
        'current_period_end' => now()->addDays(27),
        'currency' => 'SAR',
        'amount_minor' => 9900,
        'provider' => 'fake',
    ]);

    $enrollment = a4Enroll($user, $course);

    expect($enrollment->source)->toBe(EnrollmentSource::Subscription)
        ->and($enrollment->source)->not->toBe(EnrollmentSource::Free)
        ->and($enrollment->expires_at)->not->toBeNull()
        ->and($enrollment->expires_at->isFuture())->toBeTrue()
        ->and(a4HasAccess($user, $course))->toBeTrue();
});

it('removes a subscriber access once the subscription period has lapsed', function (): void {
    [$plan, $course] = a4PlanWithCourse();
    $user = User::factory()->create();

    $subscription = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->getKey(),
        'status' => SubscriptionStatus::Active->value,
        'current_period_start' => now()->subDays(3),
        'current_period_end' => now()->addDays(2),
        'currency' => 'SAR',
        'amount_minor' => 9900,
        'provider' => 'fake',
    ]);

    a4Enroll($user, $course);
    expect(a4HasAccess($user, $course))->toBeTrue();

    // The subscription lapses. Under the old behaviour the enrollment was source=free with
    // expires_at=NULL, so access survived indefinitely and nothing could take it back.
    $subscription->forceFill([
        'status' => SubscriptionStatus::Expired->value,
        'current_period_end' => now()->subDay(),
    ])->save();

    Carbon::setTestNow(now()->addDays(3));

    expect(a4HasAccess($user, $course))->toBeFalse();

    Carbon::setTestNow();
});

// -- Organization seat -----------------------------------------------------------------------------

it('records a seated employee enrolment as a company seat with an expiry', function (): void {
    [$plan, $course] = a4PlanWithCourse();
    $org = Organization::factory()->create();
    $employee = User::factory()->create();

    $subscription = (new SubscribeOrganizationAction(a4Gateway(), app(AuditLogger::class), app(SeatProvisioningPort::class)))
        ->execute($org->id, $plan, seats: 3, currency: 'SAR');

    $member = OrganizationMember::create([
        'organization_id' => $org->id,
        'user_id' => $employee->id,
        'email' => $employee->email,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(OrganizationSubscriptionService::class)->assignEmployee($subscription, $member->id);

    $enrollment = a4Enroll($employee, $course);

    // CompanySeat matters beyond bookkeeping: revokeCompanySeat() filters on this exact source, so a
    // seat recorded as `free` is unreachable by revocation.
    expect($enrollment->source)->toBe(EnrollmentSource::CompanySeat)
        ->and($enrollment->source)->not->toBe(EnrollmentSource::Free)
        ->and($enrollment->expires_at)->not->toBeNull()
        ->and(a4HasAccess($employee, $course))->toBeTrue();
});

it('removes a seated employee access when the organization subscription lapses', function (): void {
    [$plan, $course] = a4PlanWithCourse();
    $org = Organization::factory()->create();
    $employee = User::factory()->create();

    $subscription = (new SubscribeOrganizationAction(a4Gateway(), app(AuditLogger::class), app(SeatProvisioningPort::class)))
        ->execute($org->id, $plan, seats: 2, currency: 'SAR');
    $member = OrganizationMember::create([
        'organization_id' => $org->id,
        'user_id' => $employee->id,
        'email' => $employee->email,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(OrganizationSubscriptionService::class)->assignEmployee($subscription, $member->id);

    a4Enroll($employee, $course);
    expect(a4HasAccess($employee, $course))->toBeTrue();

    $subscription->forceFill([
        'status' => SubscriptionStatus::Expired->value,
        'current_period_end' => now()->subDay(),
    ])->save();

    Carbon::setTestNow(now()->addMonths(2));

    expect(a4HasAccess($employee, $course))->toBeFalse();

    Carbon::setTestNow();
});

it('withdraws a seated employee access the moment their seat is released', function (): void {
    [$plan, $course] = a4PlanWithCourse();
    $org = Organization::factory()->create();
    $employee = User::factory()->create();

    $subscription = (new SubscribeOrganizationAction(a4Gateway(), app(AuditLogger::class), app(SeatProvisioningPort::class)))
        ->execute($org->id, $plan, seats: 2, currency: 'SAR');
    $member = OrganizationMember::create([
        'organization_id' => $org->id,
        'user_id' => $employee->id,
        'email' => $employee->email,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(OrganizationSubscriptionService::class)->assignEmployee($subscription, $member->id);

    a4Enroll($employee, $course);
    expect(a4HasAccess($employee, $course))->toBeTrue();

    // Releasing the seat must take the course back immediately, not at the end of the billing
    // period. This only reaches the enrollment because it is now sourced as company_seat —
    // revokeCompanySeat() filters on exactly that, so the old `source = free` row was untouchable.
    app(OrganizationSubscriptionService::class)->unassignEmployee($subscription, $member->id);

    expect(a4HasAccess($employee, $course))->toBeFalse();
});

it('leaves a learner own purchase intact when their employer seat is released', function (): void {
    [$plan, $course] = a4PlanWithCourse();
    $org = Organization::factory()->create();
    $employee = User::factory()->create();

    // The learner also bought the course themselves.
    $order = Order::create([
        'user_id' => $employee->id,
        'status' => OrderStatus::Paid->value,
        'currency' => 'SAR',
        'subtotal_minor' => 10000,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => 10000,
        'placed_at' => now(),
        'paid_at' => now(),
    ]);
    OrderCourseGrant::create(['order_id' => $order->getKey(), 'course_id' => $course->id]);

    $subscription = (new SubscribeOrganizationAction(a4Gateway(), app(AuditLogger::class), app(SeatProvisioningPort::class)))
        ->execute($org->id, $plan, seats: 2, currency: 'SAR');
    $member = OrganizationMember::create([
        'organization_id' => $org->id,
        'user_id' => $employee->id,
        'email' => $employee->email,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(OrganizationSubscriptionService::class)->assignEmployee($subscription, $member->id);

    // Purchase outranks the seat, so the enrollment is recorded as a purchase.
    $enrollment = a4Enroll($employee, $course);
    expect($enrollment->source)->toBe(EnrollmentSource::Purchase);

    app(OrganizationSubscriptionService::class)->unassignEmployee($subscription, $member->id);

    // Revocation is scoped to company_seat rows, so what the learner paid for survives.
    expect(a4HasAccess($employee, $course))->toBeTrue();
});

// -- Purchase (must be left exactly as it was) -----------------------------------------------------

it('records a buyer enrolment as a perpetual purchase', function (): void {
    $course = Course::factory()->published()->create();
    $product = Product::factory()->create();
    $product->courses()->sync([$course->id]);

    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'status' => OrderStatus::Paid->value,
        'currency' => 'SAR',
        'subtotal_minor' => 10000,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => 10000,
        'placed_at' => now(),
        'paid_at' => now(),
    ]);
    OrderCourseGrant::create(['order_id' => $order->getKey(), 'course_id' => $course->id]);

    $enrollment = a4Enroll($user, $course);

    // A purchase is the learner's own. It must not acquire an expiry, and must not be recorded as a
    // seat that an employer's clock could withdraw.
    expect($enrollment->source)->toBe(EnrollmentSource::Purchase)
        ->and($enrollment->expires_at)->toBeNull()
        ->and(a4HasAccess($user, $course))->toBeTrue();
});

it('leaves an existing purchase enrolment untouched when the learner enrols again', function (): void {
    $course = Course::factory()->published()->create();
    $product = Product::factory()->create();
    $product->courses()->sync([$course->id]);

    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'status' => OrderStatus::Paid->value,
        'currency' => 'SAR',
        'subtotal_minor' => 10000,
        'discount_minor' => 0,
        'tax_minor' => 0,
        'total_minor' => 10000,
        'placed_at' => now(),
        'paid_at' => now(),
    ]);
    OrderCourseGrant::create(['order_id' => $order->getKey(), 'course_id' => $course->id]);

    $first = a4Enroll($user, $course);
    $second = a4Enroll($user, $course);

    expect($second->getKey())->toBe($first->getKey())
        ->and($second->source)->toBe(EnrollmentSource::Purchase)
        ->and($second->expires_at)->toBeNull();
});

it('still refuses a sold course to someone holding no entitlement at all', function (): void {
    [, $course] = a4PlanWithCourse();
    $user = User::factory()->create();

    expect(fn () => a4Enroll($user, $course))
        ->toThrow(CoursePurchaseRequiredException::class);

    $this->assertDatabaseMissing('enrollments', [
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
});
