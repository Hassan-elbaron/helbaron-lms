<?php

use App\Domains\Catalog\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the active nested category tree', function () {
    $root = Category::factory()->create(['name' => 'Development']);
    Category::factory()->childOf($root)->create(['name' => 'Web']);
    Category::factory()->create(['is_active' => false]); // inactive excluded

    $res = $this->getJson('/api/v1/categories')->assertOk();

    $devNode = collect($res->json('data'))->firstWhere('name', 'Development');
    expect($devNode)->not->toBeNull()
        ->and(collect($devNode['children'])->pluck('name'))->toContain('Web');
});
