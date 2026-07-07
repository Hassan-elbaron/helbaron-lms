<?php

namespace App\Contexts\Commerce\Listeners;

use App\Contexts\Commerce\Events\OrderRefunded;
use App\Contexts\Learning\Actions\Enrollment\UnenrollAction;
use App\Contexts\Learning\Models\Enrollment;

/**
 * Revokes granted enrollments when an order is refunded (delegates to Learning's UnenrollAction).
 */
class RevokeEnrollmentsOnRefund
{
    public function __construct(private readonly UnenrollAction $unenroll) {}

    public function handle(OrderRefunded $event): void
    {
        $order = $event->order->load('grants');

        foreach ($order->grants as $grant) {
            $enrollment = Enrollment::where('user_id', $order->user_id)
                ->where('course_id', $grant->course_id)
                ->first();

            if ($enrollment !== null) {
                $this->unenroll->execute($enrollment);
            }
        }
    }
}
