<?php

namespace App\Domains\Learning\Actions\Enrollment;

use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Models\User;
use App\Domains\Learning\Enums\EnrollmentSource;
use App\Domains\Learning\Enums\EnrollmentStatus;
use App\Domains\Learning\Events\UserEnrolled;
use App\Domains\Learning\Models\Enrollment;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Grants an enrollment idempotently. This is the entitlement seam other domains call (Commerce
 * will call it on a paid order). Re-granting an active enrollment is a no-op.
 */
class GrantEnrollmentAction extends BaseAction
{
    public function execute(User $user, Course $course, EnrollmentSource $source = EnrollmentSource::Grant): Enrollment
    {
        [$enrollment, $created] = $this->transaction(function () use ($user, $course, $source): array {
            $enrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
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
                'user_id' => $user->id,
                'course_id' => $course->id,
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
