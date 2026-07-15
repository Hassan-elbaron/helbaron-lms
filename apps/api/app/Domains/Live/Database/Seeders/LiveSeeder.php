<?php

namespace App\Domains\Live\Database\Seeders;

use App\Domains\Live\Actions\Session\ScheduleSessionAction;
use App\Domains\Live\Enums\LivePermission;
use App\Domains\Live\Models\LiveCourse;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds live permissions, a live course, and one scheduled session. Idempotent.
 */
class LiveSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (LivePermission::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        SpatieRole::findByName('admin', 'web')->givePermissionTo(LivePermission::values());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $course = LiveCourse::firstOrCreate(
            ['title' => 'Live Onboarding'],
            ['timezone' => 'Asia/Riyadh', 'is_active' => true],
        );

        if ($course->sessions()->doesntExist()) {
            app(ScheduleSessionAction::class)->execute([
                'live_course_id' => $course->id,
                'title' => 'Kickoff Session',
                'timezone' => 'Asia/Riyadh',
                'starts_at' => now()->addWeek()->setTime(18, 0)->format('Y-m-d H:i'),
                'duration_minutes' => 60,
                'capacity' => 100,
            ]);
        }
    }
}
