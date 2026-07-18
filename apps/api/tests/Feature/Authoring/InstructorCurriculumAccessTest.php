<?php

use App\Domains\Authoring\Database\Seeders\AuthoringSeeder;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    // AuthoringSeeder grants authoring.curriculum.manage to the ADMIN role only. Instructors do not
    // receive the global permission — their access comes solely from trainer ownership, which is
    // exactly what these tests exercise.
    $this->seed(AuthoringSeeder::class);
});

/** Create a user with the given roles. */
function curriculumUser(string ...$roles): User
{
    $user = User::factory()->create();
    foreach ($roles as $role) {
        // Assign the web-guard role model explicitly — robust to Sanctum's guard switch, so that
        // permission-via-role checks (e.g. can('authoring.curriculum.manage')) resolve correctly.
        $user->assignRole(SpatieRole::findByName($role, 'web'));
    }

    return $user;
}

/** Create a course, optionally assigning a trainer and a status. */
function curriculumCourse(?User $trainer = null, CourseStatus $status = CourseStatus::Draft): Course
{
    $course = Course::factory()->create(['status' => $status]);
    if ($trainer !== null) {
        $course->syncTrainers([$trainer->id]);
    }

    return $course;
}

// ── 1. Assigned instructor can perform the full curriculum lifecycle ─────────
it('lets an assigned instructor manage the full curriculum', function () {
    $instructor = curriculumUser('instructor');
    $course = curriculumCourse($instructor);
    Sanctum::actingAs($instructor);

    $this->getJson("/api/v1/admin/courses/{$course->public_id}/curriculum")->assertOk();

    $sectionId = (string) $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'Intro'])
        ->assertSuccessful()->json('data.id');

    $this->putJson("/api/v1/admin/sections/{$sectionId}", ['title' => 'Intro (edited)'])->assertSuccessful();

    $lessonId = (string) $this->postJson("/api/v1/admin/sections/{$sectionId}/lessons", ['title' => 'Lesson 1', 'type' => 'article'])
        ->assertSuccessful()->json('data.id');

    $this->putJson("/api/v1/admin/lessons/{$lessonId}", ['title' => 'Lesson 1 (edited)'])->assertSuccessful();
    $this->putJson("/api/v1/admin/sections/{$sectionId}/lessons/order", ['order' => [$lessonId]])->assertSuccessful();
    $this->putJson("/api/v1/admin/courses/{$course->public_id}/sections/order", ['order' => [$sectionId]])->assertSuccessful();
    $this->deleteJson("/api/v1/admin/lessons/{$lessonId}")->assertSuccessful();
    $this->deleteJson("/api/v1/admin/sections/{$sectionId}")->assertSuccessful();
});

// ── 2. Unassigned instructor is denied ───────────────────────────────────────
it('denies an unassigned instructor', function () {
    $instructor = curriculumUser('instructor');
    $course = curriculumCourse(); // no trainer assignment
    Sanctum::actingAs($instructor);

    $this->getJson("/api/v1/admin/courses/{$course->public_id}/curriculum")->assertForbidden();
    $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'X'])->assertForbidden();
});

// ── 3. Admin with the global permission still works ──────────────────────────
it('lets an admin with the global permission manage any course', function () {
    $admin = curriculumUser('admin'); // the admin role carries authoring.curriculum.manage (AuthoringSeeder)
    $course = curriculumCourse(); // not a trainer — relies on the global permission
    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'X'])->assertSuccessful();
});

// ── 4. Super-admin bypass is preserved ───────────────────────────────────────
it('preserves the super_admin bypass', function () {
    $superAdmin = curriculumUser('super_admin');
    $course = curriculumCourse();
    Sanctum::actingAs($superAdmin);

    $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'X'])->assertSuccessful();
});

// ── 5. Student is denied ─────────────────────────────────────────────────────
it('denies a student', function () {
    $student = curriculumUser('student');
    $course = curriculumCourse();
    Sanctum::actingAs($student);

    $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'X'])->assertForbidden();
});

// ── 6. Cross-course section tampering is denied ──────────────────────────────
it('denies cross-course section tampering', function () {
    $instructor = curriculumUser('instructor');
    curriculumCourse($instructor); // instructor owns *a* course, but not this one
    $other = curriculumCourse();
    $foreignSection = Section::factory()->create(['course_id' => $other->id]);
    Sanctum::actingAs($instructor);

    $this->putJson("/api/v1/admin/sections/{$foreignSection->public_id}", ['title' => 'hacked'])->assertForbidden();
    $this->deleteJson("/api/v1/admin/sections/{$foreignSection->public_id}")->assertForbidden();
});

// ── 7. Cross-course lesson tampering is denied ───────────────────────────────
it('denies cross-course lesson tampering', function () {
    $instructor = curriculumUser('instructor');
    curriculumCourse($instructor);
    $other = curriculumCourse();
    $foreignSection = Section::factory()->create(['course_id' => $other->id]);
    $foreignLesson = Lesson::factory()->create(['section_id' => $foreignSection->id]);
    Sanctum::actingAs($instructor);

    $this->putJson("/api/v1/admin/lessons/{$foreignLesson->public_id}", ['title' => 'hacked'])->assertForbidden();
    $this->deleteJson("/api/v1/admin/lessons/{$foreignLesson->public_id}")->assertForbidden();
});

// ── 8a. Section reorder ignores foreign ids (no tampering) ───────────────────
it('ignores foreign section ids in a reorder payload', function () {
    $instructor = curriculumUser('instructor');
    $mine = curriculumCourse($instructor);
    $mineSection = Section::factory()->create(['course_id' => $mine->id, 'position' => 0]);
    $other = curriculumCourse();
    $foreignSection = Section::factory()->create(['course_id' => $other->id, 'position' => 5]);
    Sanctum::actingAs($instructor);

    $this->putJson("/api/v1/admin/courses/{$mine->public_id}/sections/order", [
        'order' => [$foreignSection->public_id, $mineSection->public_id],
    ])->assertSuccessful();

    $foreignSection->refresh();
    expect($foreignSection->course_id)->toBe($other->id);
    expect($foreignSection->position)->toBe(5); // untouched
});

// ── 8b. Whole-tree reorder cannot steal a foreign lesson ─────────────────────
it('cannot steal a foreign lesson via the whole-tree reorder', function () {
    $instructor = curriculumUser('instructor');
    $mine = curriculumCourse($instructor);
    $mineSection = Section::factory()->create(['course_id' => $mine->id]);
    $other = curriculumCourse();
    $foreignSection = Section::factory()->create(['course_id' => $other->id]);
    $foreignLesson = Lesson::factory()->create(['section_id' => $foreignSection->id]);
    Sanctum::actingAs($instructor);

    $this->putJson("/api/v1/admin/courses/{$mine->public_id}/curriculum/order", [
        'tree' => [['id' => $mineSection->public_id, 'lessons' => [$foreignLesson->public_id]]],
    ])->assertSuccessful();

    $foreignLesson->refresh();
    expect($foreignLesson->section_id)->toBe($foreignSection->id); // NOT re-parented into the attacker's course
});

// ── 9. Isolation between instructors (org boundary via assignment) ───────────
it('isolates one instructor from another instructor\'s course', function () {
    $owner = curriculumUser('instructor');
    $intruder = curriculumUser('instructor');
    $course = curriculumCourse($owner);
    $section = Section::factory()->create(['course_id' => $course->id]);
    Sanctum::actingAs($intruder);

    $this->putJson("/api/v1/admin/sections/{$section->public_id}", ['title' => 'x'])->assertForbidden();
});

// ── Archived business rule: assigned instructor cannot edit an archived course ─
it('denies an assigned instructor on an archived course', function () {
    $instructor = curriculumUser('instructor');
    $course = curriculumCourse($instructor, CourseStatus::Archived);
    Sanctum::actingAs($instructor);

    $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'x'])->assertForbidden();
});
