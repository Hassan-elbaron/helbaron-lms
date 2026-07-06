<?php

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only published + public courses', function () {
    Course::factory()->published()->create(['title' => 'Visible Course']);
    Course::factory()->create(['title' => 'Draft Course']);              // draft
    Course::factory()->published()->hidden()->create(['title' => 'Private Course']);

    $res = $this->getJson('/api/v1/courses')->assertOk();

    expect($res->json('data'))->toHaveCount(1)
        ->and($res->json('data.0.title'))->toBe('Visible Course')
        ->and($res->json('meta.total'))->toBe(1);
});

it('filters by category and searches by title', function () {
    $cat = Category::factory()->create();
    $matching = Course::factory()->published()->create(['title' => 'Laravel Mastery']);
    $matching->categories()->sync([$cat->id]);
    Course::factory()->published()->create(['title' => 'Cooking Basics']);

    $this->getJson('/api/v1/courses?category='.$cat->public_id)
        ->assertOk()->assertJsonPath('meta.total', 1);

    $this->getJson('/api/v1/courses?q=Laravel')
        ->assertOk()->assertJsonPath('data.0.title', 'Laravel Mastery');
});

it('filters featured courses', function () {
    Course::factory()->published()->featured()->create();
    Course::factory()->published()->create();

    $this->getJson('/api/v1/courses?featured=1')
        ->assertOk()->assertJsonPath('meta.total', 1);
});
