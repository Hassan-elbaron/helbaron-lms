<?php

namespace App\Domains\Authoring\Policies;

use App\Domains\Authoring\Models\Section;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;
use Illuminate\Support\Facades\Gate;

class SectionPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function update(Actor $user, Section $section): bool
    {
        return $this->managesCourse($user, $section);
    }

    public function delete(Actor $user, Section $section): bool
    {
        return $this->managesCourse($user, $section);
    }

    /**
     * Authorize through the section's parent course — the single source of truth for curriculum
     * access (global permission OR assigned-trainer ownership, via the authoring.manage-curriculum
     * gate). Resolving via the parent guarantees a section can never be managed by someone who
     * cannot manage its course, which closes cross-course tampering (a foreign section id resolves
     * to a course the actor does not train).
     */
    private function managesCourse(Actor $user, Section $section): bool
    {
        $course = $section->course;

        return $course !== null && Gate::forUser($user)->allows('authoring.manage-curriculum', $course);
    }
}
