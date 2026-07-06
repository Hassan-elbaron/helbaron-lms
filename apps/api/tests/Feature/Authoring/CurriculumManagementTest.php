<?php

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

it('creates sections and lessons and returns the curriculum tree', function () {
    $course = Course::factory()->create();

    $section = $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'Basics'])
        ->assertCreated()->json('data.id');

    $this->postJson("/api/v1/admin/sections/{$section}/lessons", [
        'title' => 'Intro',
        'type' => 'video',
    ])->assertCreated()->assertJsonPath('data.type', 'video');

    $tree = $this->getJson("/api/v1/admin/courses/{$course->public_id}/curriculum")->assertOk();

    expect($tree->json('data.sections'))->toHaveCount(1)
        ->and($tree->json('data.sections.0.title'))->toBe('Basics')
        ->and($tree->json('data.sections.0.lessons.0.title'))->toBe('Intro');
});

it('reorders lessons within a section', function () {
    $course = Course::factory()->create();
    $section = $this->postJson("/api/v1/admin/courses/{$course->public_id}/sections", ['title' => 'S'])->json('data.id');
    $a = $this->postJson("/api/v1/admin/sections/{$section}/lessons", ['title' => 'A', 'type' => 'article'])->json('data.id');
    $b = $this->postJson("/api/v1/admin/sections/{$section}/lessons", ['title' => 'B', 'type' => 'article'])->json('data.id');

    $this->putJson("/api/v1/admin/sections/{$section}/lessons/order", ['order' => [$b, $a]])->assertOk();

    $tree = $this->getJson("/api/v1/admin/courses/{$course->public_id}/curriculum")->json('data.sections.0.lessons');
    expect($tree[0]['title'])->toBe('B');
});
