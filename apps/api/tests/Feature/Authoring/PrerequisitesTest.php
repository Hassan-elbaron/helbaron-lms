<?php

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Authoring\Models\Section;
use App\Domains\Catalog\Models\Course;
use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);
});

it('sets prerequisites and rejects cycles', function () {
    $course = Course::factory()->create();
    $section = Section::factory()->create(['course_id' => $course->id]);
    $a = Lesson::factory()->create(['section_id' => $section->id]);
    $b = Lesson::factory()->create(['section_id' => $section->id]);

    // B depends on A -> ok
    $this->putJson("/api/v1/admin/lessons/{$b->public_id}/prerequisites", ['prerequisites' => [$a->public_id]])
        ->assertOk();

    // A depends on B -> cycle
    $this->putJson("/api/v1/admin/lessons/{$a->public_id}/prerequisites", ['prerequisites' => [$b->public_id]])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'AUTHORING_PREREQUISITE_CYCLE');
});
