<?php

namespace App\Contexts\Commerce\Actions\Payment;

use App\Contexts\Commerce\Events\OrderFulfilled;
use App\Contexts\Commerce\Models\Order;
use App\Contexts\Commerce\Models\OrderCourseGrant;
use App\Contexts\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Grants course enrollments for a paid order — ONLY when the order is paid AND its contract is
 * accepted. Idempotent via order_course_grants. Enrollment is delegated to Learning's action
 * (Commerce never writes enrollments directly).
 */
class FulfillOrderAction extends BaseAction
{
    public function __construct(private readonly GrantEnrollmentAction $grant) {}

    public function execute(Order $order): bool
    {
        return (bool) $this->transaction(function () use ($order): bool {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $order->isPaid()) {
                return false;
            }

            $contract = $order->contract;
            if ($contract !== null && ! $contract->isAccepted()) {
                return false; // wait for acceptance
            }

            if ($order->fulfilled_at !== null) {
                return false; // already fulfilled
            }

            $order->load('items.product.courses');

            foreach ($order->items as $item) {
                foreach ($item->product->courses as $course) {
                    $grant = OrderCourseGrant::firstOrCreate(
                        ['order_id' => $order->id, 'course_id' => $course->id],
                        ['granted_at' => now()],
                    );

                    if ($grant->wasRecentlyCreated) {
                        $this->grant->executeByUserId($order->user_id, $course->id, EnrollmentSource::Purchase);
                    }
                }
            }

            $order->forceFill(['fulfilled_at' => now()])->save();

            return true;
        }) ? $this->afterFulfilled($order) : false;
    }

    private function afterFulfilled(Order $order): bool
    {
        OrderFulfilled::dispatch($order->refresh());

        return true;
    }
}
