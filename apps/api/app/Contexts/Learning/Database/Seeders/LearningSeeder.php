<?php

namespace App\Contexts\Learning\Database\Seeders;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Enums\Role;
use App\Platform\Identity\Models\User;
use App\Contexts\Learning\Actions\Enrollment\GrantEnrollmentAction;
use App\Contexts\Learning\Enums\EnrollmentSource;
use App\Contexts\Learning\Enums\LessonProgressStatus;
use App\Contexts\Learning\Services\ProgressService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Enrolls a sample student in the first published course and records some progress. Idempotent.
 */
class LearningSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::query()->orderBy('id')->first();
        if ($course === null) {
            return;
        }

        $student = User::firstOrCreate(
            ['email' => 'student@helbaron.local'],
            ['name' => 'Sample Student', 'password' => Hash::make('password'), 'is_active' => true, 'email_verified_at' => now()],
        );
        $student->assignRole(Role::Student->value);

        $enrollment = app(GrantEnrollmentAction::class)->execute($student, $course, EnrollmentSource::Free);

        $firstLesson = Lesson::whereIn('section_id', Section::where('course_id', $course->id)->pluck('id'))
            ->published()->orderBy('position')->first();

        if ($firstLesson !== null) {
            app(ProgressService::class)->record($enrollment, $firstLesson, LessonProgressStatus::Completed);
        }
    }
}
