<?php

use App\Platform\Identity\Database\Seeders\RolePermissionSeeder;
use App\Platform\Identity\Enums\Role;
use App\Platform\Identity\Models\User;
use App\Platform\Pages\Database\Seeders\StaticPagesSeeder;
use App\Platform\Pages\Enums\PageStatus;
use App\Platform\Pages\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

it('returns a published page by slug with the full bilingual payload + resolved SEO', function () {
    StaticPage::factory()->slug('about')->published()->create([
        'title' => ['en' => 'About', 'ar' => 'من نحن'],
        'body' => ['en' => '<p>Hello</p>', 'ar' => '<p>مرحبا</p>'],
        'excerpt' => ['en' => 'Intro', 'ar' => 'مقدمة'],
    ]);

    $res = $this->getJson('/api/v1/pages/about')->assertOk();

    expect($res->json('data.slug'))->toBe('about')
        ->and($res->json('data.title.en'))->toBe('About')
        ->and($res->json('data.body.ar'))->toBe('<p>مرحبا</p>')
        ->and($res->json('data.seo.canonical'))->toBe('/about')
        ->and($res->json('data.seo.robots_index'))->toBeTrue();
});

it('404s a draft page on the public endpoint', function () {
    StaticPage::factory()->slug('secret')->draft()->create();

    $this->getJson('/api/v1/pages/secret')->assertNotFound();
});

it('404s a page scheduled to publish in the future', function () {
    StaticPage::factory()->slug('soon')->scheduledFuture()->create();

    $this->getJson('/api/v1/pages/soon')->assertNotFound();
});

it('404s a page whose unpublish time has passed', function () {
    StaticPage::factory()->slug('gone')->expired()->create();

    $this->getJson('/api/v1/pages/gone')->assertNotFound();
});

it('lists only live published pages on the index endpoint', function () {
    StaticPage::factory()->slug('live-one')->published()->create();
    StaticPage::factory()->slug('draft-one')->draft()->create();
    StaticPage::factory()->slug('future-one')->scheduledFuture()->create();

    $slugs = collect($this->getJson('/api/v1/pages')->assertOk()->json('data.pages'))->pluck('slug');

    expect($slugs)->toContain('live-one')
        ->not->toContain('draft-one')
        ->not->toContain('future-one');
});

it('sanitizes body HTML on save — scripts are stripped', function () {
    $page = StaticPage::factory()->published()->create([
        'body' => [
            'en' => '<p>Safe</p><script>alert(1)</script>',
            'ar' => '<p>آمن</p><script>alert(2)</script>',
        ],
    ]);

    expect($page->fresh()->body['en'])->not->toContain('<script>')
        ->and($page->fresh()->body['en'])->toContain('Safe')
        ->and($page->fresh()->body['ar'])->not->toContain('<script>');
});

it('records a version snapshot on every update', function () {
    $page = StaticPage::factory()->published()->create(['body' => ['en' => '<p>v0</p>', 'ar' => '<p>v0</p>']]);

    expect($page->versions()->count())->toBe(0);

    $page->update(['body' => ['en' => '<p>v1</p>', 'ar' => '<p>v1</p>']]);
    $page->update(['body' => ['en' => '<p>v2</p>', 'ar' => '<p>v2</p>']]);

    expect($page->versions()->count())->toBe(2)
        ->and($page->versions()->max('version'))->toBe(2);
});

it('restores prior content on rollback and records the rollback as a new version', function () {
    $page = StaticPage::factory()->published()->create(['body' => ['en' => '<p>v0</p>', 'ar' => '<p>v0</p>']]);

    $page->update(['body' => ['en' => '<p>version one</p>', 'ar' => '<p>v1</p>']]); // version 1
    $page->update(['body' => ['en' => '<p>version two</p>', 'ar' => '<p>v2</p>']]); // version 2

    $page->rollbackTo(1); // restores "version one", recorded as version 3

    expect($page->fresh()->body['en'])->toContain('version one')
        ->and($page->versions()->max('version'))->toBe(3);
});

it('publish() flips status to Published and stamps published_at', function () {
    $page = StaticPage::factory()->draft()->create();

    expect($page->isLive())->toBeFalse();

    $page->publish();

    expect($page->fresh()->status)->toBe(PageStatus::Published)
        ->and($page->fresh()->published_at)->not->toBeNull()
        ->and($page->fresh()->isLive())->toBeTrue();
});

it('rejects the preview endpoint for guests', function () {
    StaticPage::factory()->slug('draft-preview')->draft()->create();

    $this->getJson('/api/v1/pages/draft-preview/preview')->assertUnauthorized();
});

it('rejects the preview endpoint for non-admins', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole(SpatieRole::findByName(Role::Student->value, 'web'));
    Sanctum::actingAs($user);

    StaticPage::factory()->slug('draft-preview')->draft()->create();

    $this->getJson('/api/v1/pages/draft-preview/preview')->assertForbidden();
});

it('returns the draft to an admin on the preview endpoint', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(SpatieRole::findByName(Role::Admin->value, 'web'));
    Sanctum::actingAs($admin);

    StaticPage::factory()->slug('draft-preview')->draft()->create([
        'title' => ['en' => 'Draft Title', 'ar' => 'عنوان'],
    ]);

    $this->getJson('/api/v1/pages/draft-preview/preview')
        ->assertOk()
        ->assertJsonPath('data.title.en', 'Draft Title')
        ->assertJsonPath('data.status', 'draft');
});

it('seeds the migrated static pages as published records', function () {
    $this->seed(StaticPagesSeeder::class);

    foreach (['about', 'contact', 'privacy', 'terms', 'cookies', 'refund-policy', 'faq', 'careers', 'help'] as $slug) {
        $this->getJson("/api/v1/pages/{$slug}")->assertOk();
    }
});
