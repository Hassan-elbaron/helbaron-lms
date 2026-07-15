<?php

namespace App\Contexts\Learning\Actions\Enrollment;

use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Enums\EnrollmentStatus;
use App\Contexts\Learning\Events\UserEnrolled;
use App\Contexts\Learning\Models\Enrollment;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Grants an enrollment idempotently. This is the entitlement seam other domains call (Commerce
 * will call it on a paid order). Re-granting an active enrollment is a no-op. Callers pass ids
 * (user id + course id) — no Identity/Catalog model dependency.
 */
class GrantEnrollmentAction extends BaseAction
{
    public function executeByUserId(int $userId, int $courseId, EnrollmentSource $source = EnrollmentSource::Grant): Enrollment
    {
        [$enrollment, $created] = $this->transaction(function () use ($userId, $courseId, $source): array {
            $enrollment = Enrollment::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->lockForUpdate()
                ->first();

            if ($enrollment !== null) {
                // Reactivate a cancelled enrollment; otherwise leave as-is (idempotent).
                if ($enrollment->status === EnrollmentStatus::Cancelled) {
                    $enrollment->forceFill([
                        'status' => EnrollmentStatus::Active->value,
                        'enrolled_at' => $enrollment->enrolled_at ?? now(),
                    ])->save();
                }

                return [$enrollment, false];
            }

            $enrollment = Enrollment::create([
                'user_id' => $userId,
                'course_id' => $courseId,
                'status' => EnrollmentStatus::Active->value,
                'source' => $source->value,
                'enrolled_at' => now(),
            ]);

            return [$enrollment, true];
        });

        if ($created) {
            UserEnrolled::dispatch($enrollment);
        }

        return $enrollment;
    }
}
