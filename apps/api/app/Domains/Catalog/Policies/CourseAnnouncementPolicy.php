<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Models\CourseAnnouncement;
use App\Platform\Identity\Contracts\Actor;
use App\Platform\Shared\Policies\BasePolicy;

/**
 * Announcement authorization. Admins manage everything; instructors manage announcements only
 * for courses they train (ownership-scoped). super_admin bypasses via before().
 */
class CourseAnnouncementPolicy extends BasePolicy
{
    public function before(mixed $user, string $ability): ?bool
    {
        if ($user instanceof Actor && ($user->hasRole('super_admin') || $user->hasRole('admin'))) {
            return true;
        }

        return null;
    }

    public function view(Actor $user, CourseAnnouncement $announcement): bool
    {
        return $this->manages($user, $announcement);
    }

    public function update(Actor $user, CourseAnnouncement $announcement): bool
    {
        return $this->manages($user, $announcement);
    }

    public function delete(Actor $user, CourseAnnouncement $announcement): bool
    {
        return $this->manages($user, $announcement);
    }

    /** The author, or an instructor who trains the announcement's course, may manage it. */
    private function manages(Actor $user, CourseAnnouncement $announcement): bool
    {
        if ((int) $announcement->author_id === $user->actorId()) {
            return true;
        }

        $course = $announcement->course()->first();

        return $user->hasRole('instructor')
            && $course instanceof Course
            && $course->isTrainedBy($user->actorId());
    }
}
