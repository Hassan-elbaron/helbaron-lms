<?php

namespace App\Domains\Assessment\Policies;

use App\Domains\Assessment\Models\Assessment;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;
use Illuminate\Support\Facades\Gate;

/**
 * Every ability resolves through the single `assessment.manage-assessment` gate, which resolves course
 * ownership through CourseAccessPort. Ownership logic is therefore defined in exactly one place and
 * shared with curriculum authoring — an instructor who may edit a course's lessons may edit its
 * assessments, and nothing else.
 */
class AssessmentPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function view(Actor $user, Assessment $assessment): bool
    {
        return $this->manages($user, $assessment);
    }

    public function update(Actor $user, Assessment $assessment): bool
    {
        return $this->manages($user, $assessment);
    }

    public function delete(Actor $user, Assessment $assessment): bool
    {
        return $this->manages($user, $assessment);
    }

    private function manages(Actor $user, Assessment $assessment): bool
    {
        return Gate::forUser($user)->allows('assessment.manage-assessment', $assessment);
    }
}
