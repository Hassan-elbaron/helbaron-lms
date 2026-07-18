<?php

namespace App\Domains\Authoring\Policies;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;
use Illuminate\Support\Facades\Gate;

class LessonPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && $user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    public function update(Actor $user, Lesson $lesson): bool
    {
        return $this->managesCourse($user, $lesson);
    }

    public function delete(Actor $user, Lesson $lesson): bool
    {
        return $this->managesCourse($user, $lesson);
    }

    /**
     * Authorize through the lesson's parent course (lesson → section → course) — the single source
     * of truth for curriculum access. Resolving the whole ancestry means a lesson whose section
     * belongs to another course is authorized against that course, so cross-course tampering (a
     * foreign lesson id) is denied automatically.
     */
    private function managesCourse(Actor $user, Lesson $lesson): bool
    {
        $section = $lesson->section;
        if (! $section instanceof Section) {
            return false;
        }

        $course = $section->course;

        return $course !== null && Gate::forUser($user)->allows('authoring.manage-curriculum', $course);
    }
}
