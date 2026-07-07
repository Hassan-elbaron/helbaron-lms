<?php

namespace App\Domains\Authoring\Database\Seeders;

use App\Domains\Authoring\Enums\AuthoringPermission;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Platform\Identity\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds authoring permissions (granted to admin + instructor) and a sample curriculum on the
 * first course. Idempotent.
 */
class AuthoringSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AuthoringPermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ([Role::Admin, Role::Instructor] as $role) {
            $model = SpatieRole::findByName($role->value, 'web');
            $model->givePermissionTo(AuthoringPermission::values());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $course = Course::query()->orderBy('id')->first();

        if ($course && Section::where('course_id', $course->id)->doesntExist()) {
            $intro = Section::create([
                'course_id' => $course->id,
                'title' => 'Introduction',
                'position' => 1,
                'publish_state' => PublishState::Published->value,
            ]);

            Lesson::create([
                'section_id' => $intro->id,
                'title' => 'Welcome',
                'type' => LessonType::Video->value,
                'position' => 1,
                'publish_state' => PublishState::Published->value,
                'is_preview' => true,
                'content' => [],
            ]);

            Lesson::create([
                'section_id' => $intro->id,
                'title' => 'Course Overview',
                'type' => LessonType::Article->value,
                'position' => 2,
                'publish_state' => PublishState::Published->value,
                'content' => ['body' => 'Welcome to the course.'],
            ]);
        }
    }
}
