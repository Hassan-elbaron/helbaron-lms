<?php

namespace App\Domains\Catalog\Http\Controllers\Api\V1\Instructor;

use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Contracts\Actor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base for the Instructor Portal (/api/v1/teach/*). Every action is (a) role-gated to the
 * instructor / admin / super_admin roles and (b) ownership-scoped: a course must be trained by
 * the current user (privileged admins bypass ownership). No global course-manage grant is used —
 * authorization is entirely scoped here.
 */
abstract class InstructorController extends Controller
{
    /** @var list<string> */
    private const PORTAL_ROLES = ['instructor', 'admin', 'super_admin'];

    /** Resolve the authenticated instructor; 403 if the principal lacks a portal role. */
    protected function instructor(Request $request): Actor
    {
        $user = $request->user();

        if (! $user instanceof Actor || ! $this->hasPortalRole($user)) {
            throw new AccessDeniedHttpException('Instructor access required.');
        }

        return $user;
    }

    /**
     * Enforce that the current instructor may act on the given course. Instructors are scoped to
     * courses they train; admins / super_admins bypass ownership. 404 keeps non-owned courses
     * indistinguishable from missing ones.
     */
    protected function ownedCourse(Request $request, Course $course): Course
    {
        $user = $this->instructor($request);

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return $course;
        }

        if (! $course->isTrainedBy($user->actorId())) {
            throw new NotFoundHttpException('Course not found.');
        }

        return $course;
    }

    private function hasPortalRole(Actor $user): bool
    {
        foreach (self::PORTAL_ROLES as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
