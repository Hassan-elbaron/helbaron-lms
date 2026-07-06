<?php

use App\Domains\Identity\Database\Seeders\RolePermissionSeeder;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $this->actingAs($admin);
});

it('loads every registered resource index page', function () {
    $panel = filament()->getPanel('admin');
    $resources = $panel->getResources();

    expect($resources)->not->toBeEmpty();

    foreach ($resources as $resource) {
        $url = $resource::getUrl('index', panel: 'admin');
        $this->get($url)->assertSuccessful();
    }
})->group('filament');

it('registers resources for every domain navigation group', function () {
    $panel = filament()->getPanel('admin');
    $groups = collect($panel->getResources())
        ->map(fn ($r) => $r::getNavigationGroup())
        ->unique()
        ->filter()
        ->values();

    expect($groups)->toContain('Identity', 'Catalog', 'Commerce', 'Learning', 'Analytics', 'Notifications');
});
