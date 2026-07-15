<?php

use App\Platform\Homepage\Models\HomepageSection;
use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Enums\Role;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

it('returns enabled published sections in position order with the SEO block folded out', function () {
    HomepageSection::factory()->block('hero')->published()->create(['position' => 20]);
    HomepageSection::factory()->block('features')->published()->create(['position' => 10]);
    HomepageSection::factory()->block('seo')->published()->create(['position' => 90]);

    $res = $this->getJson('/api/v1/homepage')->assertOk();

    // Ordered by position (features before hero); SEO is not part of `sections`.
    expect($res->json('data.sections'))->toHaveCount(2)
        ->and($res->json('data.sections.0.type'))->toBe('features')
        ->and($res->json('data.sections.1.type'))->toBe('hero')
        ->and($res->json('data.seo.meta_title.en'))->not->toBeNull();
});

it('excludes disabled sections from the public homepage', function () {
    HomepageSection::factory()->block('hero')->published()->create();
    HomepageSection::factory()->block('partners')->disabled()->published()->create();

    $res = $this->getJson('/api/v1/homepage')->assertOk();

    $types = collect($res->json('data.sections'))->pluck('type');
    expect($types)->toContain('hero')->not->toContain('partners');
});

it('serves the published snapshot, not later draft edits, until re-published', function () {
    $hero = HomepageSection::factory()->block('hero')->published()->create();

    // Edit the draft only (no publish yet).
    $content = $hero->content;
    $content['headline']['en'] = 'DRAFT ONLY HEADLINE';
    $hero->update(['content' => $content]);

    $this->getJson('/api/v1/homepage')
        ->assertOk()
        ->assertJsonPath('data.sections.0.content.headline.en', 'Master the core. Lead the future.');

    // Publish snapshots the draft into the live copy.
    $hero->refresh()->publish();

    $this->getJson('/api/v1/homepage')
        ->assertOk()
        ->assertJsonPath('data.sections.0.content.headline.en', 'DRAFT ONLY HEADLINE');
});

it('publish() copies draft content into the published snapshot and stamps published_at', function () {
    $hero = HomepageSection::factory()->block('hero')->create(); // unpublished

    expect($hero->published_at)->toBeNull()
        ->and($hero->published_content)->toBeNull();

    $hero->publish();

    expect($hero->published_at)->not->toBeNull()
        ->and($hero->published_content)->toEqual($hero->content);
});

it('rejects the preview endpoint for guests', function () {
    $this->getJson('/api/v1/homepage/preview')->assertUnauthorized();
});

it('rejects the preview endpoint for non-admins', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(SpatieRole::findByName(Role::Student->value, 'web'));
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/homepage/preview')->assertForbidden();
});

it('returns draft content to an admin on the preview endpoint', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(SpatieRole::findByName(Role::Admin->value, 'web'));
    Sanctum::actingAs($admin);

    $hero = HomepageSection::factory()->block('hero')->published()->create();
    $content = $hero->content;
    $content['headline']['en'] = 'DRAFT PREVIEW HEADLINE';
    $hero->update(['content' => $content]); // draft ahead of published snapshot

    $this->getJson('/api/v1/homepage/preview')
        ->assertOk()
        ->assertJsonPath('data.sections.0.content.headline.en', 'DRAFT PREVIEW HEADLINE');
});
