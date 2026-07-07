<?php

namespace App\Domains\Learning\Actions\Enrollment;

use App\Domains\Learning\Enums\EnrollmentStatus;
use App\Domains\Learning\Models\Enrollment;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Cancels an enrollment (used by Commerce on refund later). Progress is preserved.
 */
class UnenrollAction extends BaseAction
{
    public function execute(Enrollment $enrollment): Enrollment
    {
        return $this->transaction(function () use ($enrollment): Enrollment {
            $enrollment->forceFill(['status' => EnrollmentStatus::Cancelled->value])->save();

            return $enrollment;
        });
    }
}
