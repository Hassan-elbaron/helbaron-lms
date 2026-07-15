<?php

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use App\Platform\Pages\Models\StaticPage;
use App\Platform\Seo\Database\Seeders\SeoSeeder;
use App\Platform\Seo\Enums\SeoEntityType;
use App\Platform\Seo\Models\SeoMeta;
use App\Platform\Seo\Rules\UniqueCanonical;
use App\Platform\Seo\Rules\ValidCanonical;
use App\Platform\Seo\Rules\ValidJsonLd;
use App\Platform\Seo\Services\SeoResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Run a ValidationRule and capture whether it failed. */
function seoRuleFails(object $rule, mixed $value): bool
{
    $failed = false;
    $rule->validate('field', $value, function () use (&$failed): void {
        $failed = true;
    });

    return $failed;
}

it('merges a stored override over entity-derived defaults', function () {
    $course = Course::factory()->create(['title' => 'Original Course Title', 'description' => 'Derived description.']);

    SeoMeta::factory()->forEntity(SeoEntityType::Course, $course->public_id)->create([
        'meta_title' => ['en' => 'Overridden Title', 'ar' => 'عنوان'],
        // meta_description left unset (null) -> should fall back to the course description.
        'meta_description' => null,
    ]);

    $resolved = app(SeoResolver::class)->resolve(SeoEntityType::Course, $course->public_id);

    expect($resolved['meta_title']['en'])->toBe('Overridden Title')
        ->and($resolved['meta_description']['en'])->toBe('Derived description.')
        ->and($resolved['canonical'])->toBe('/courses/'.$course->public_id);
});

it('resolves SEO for a course over the public endpoint by slug', function () {
    $course = Course::factory()->create(['title' => 'Fetchable Course', 'slug' => 'fetchable-course']);

    SeoMeta::factory()->forEntity(SeoEntityType::Course, 'fetchable-course')->create([
        'meta_title' => ['en' => 'SERP Title', 'ar' => 'عنوان'],
    ]);

    $this->getJson('/api/v1/seo/course/fetchable-course')
        ->assertOk()
        ->assertJsonPath('data.meta_title.en', 'SERP Title')
        ->assertJsonPath('data.canonical', '/courses/fetchable-course')
        ->assertJsonPath('data.robots_index', true);
});

it('404s an unknown entity type on the public endpoint', function () {
    $this->getJson('/api/v1/seo/nonsense/some-key')->assertNotFound();
});

it('rejects an invalid/unsafe canonical via the rule', function () {
    expect(seoRuleFails(new ValidCanonical, 'javascript:alert(1)'))->toBeTrue()
        ->and(seoRuleFails(new ValidCanonical, '//evil.example.com'))->toBeTrue()
        ->and(seoRuleFails(new ValidCanonical, 'https://helbaron.test/courses/x'))->toBeFalse()
        ->and(seoRuleFails(new ValidCanonical, '/courses/x'))->toBeFalse()
        ->and(seoRuleFails(new ValidCanonical, null))->toBeFalse();
});

it('rejects malformed JSON-LD via the rule', function () {
    expect(seoRuleFails(new ValidJsonLd, '{not valid json'))->toBeTrue()
        ->and(seoRuleFails(new ValidJsonLd, '"just a string"'))->toBeTrue()
        ->and(seoRuleFails(new ValidJsonLd, '{"@type":"WebPage"}'))->toBeFalse()
        ->and(seoRuleFails(new ValidJsonLd, null))->toBeFalse();
});

it('detects a duplicate canonical across records', function () {
    SeoMeta::factory()->forEntity(SeoEntityType::MarketingPage, 'pricing')->canonical('/pricing')->create();

    // A different record reusing the same canonical must be flagged.
    expect(seoRuleFails(new UniqueCanonical, '/pricing'))->toBeTrue();

    // The owning record (excluded by id) must NOT be flagged against itself.
    $owner = SeoMeta::query()->where('entity_key', 'pricing')->firstOrFail();
    expect(seoRuleFails(new UniqueCanonical($owner->id), '/pricing'))->toBeFalse();
});

it('surfaces robots_index=false in the resolved payload', function () {
    SeoMeta::factory()->forEntity(SeoEntityType::MarketingPage, 'secret')->noindex()->create();

    $this->getJson('/api/v1/seo/marketing_page/secret')
        ->assertOk()
        ->assertJsonPath('data.robots_index', false);
});

it('dedupes and excludes disabled/noindex rows from the sitemap endpoint', function () {
    SeoMeta::factory()->forEntity(SeoEntityType::MarketingPage, 'a')->canonical('/shared')->create();
    SeoMeta::factory()->forEntity(SeoEntityType::MarketingPage, 'b')->canonical('/shared')->create(); // duplicate URL
    SeoMeta::factory()->forEntity(SeoEntityType::MarketingPage, 'c')->canonical('/hidden')->sitemapDisabled()->create();
    SeoMeta::factory()->forEntity(SeoEntityType::MarketingPage, 'd')->canonical('/no-index')->noindex()->create();

    $entries = collect($this->getJson('/api/v1/seo/sitemap')->assertOk()->json('data.entries'));
    $urls = $entries->pluck('url');

    expect($urls->filter(fn ($u) => $u === '/shared')->count())->toBe(1)
        ->and($urls)->not->toContain('/hidden')
        ->not->toContain('/no-index');
});

it('produces a non-empty, deduped sitemap from seeded published content', function () {
    // Real published content the seeder should surface into sitemap-enabled SEO rows.
    Course::factory()->published()->create(['slug' => 'seeded-course']);
    Category::factory()->create(['slug' => 'seeded-category', 'is_active' => true]);
    StaticPage::factory()->published()->create(['slug' => 'seeded-page']);

    $this->seed(SeoSeeder::class);

    $entries = collect($this->getJson('/api/v1/seo/sitemap')->assertOk()->json('data.entries'));
    $urls = $entries->pluck('url');

    // Non-empty and includes the homepage plus the derived entity URLs.
    expect($entries->count())->toBeGreaterThan(0)
        ->and($urls)->toContain('/')
        ->and($urls)->toContain('/courses/seeded-course')
        ->and($urls)->toContain('/categories/seeded-category')
        ->and($urls)->toContain('/p/seeded-page');

    // Deduped: no URL appears twice.
    expect($urls->count())->toBe($urls->unique()->count());

    // Re-seeding is idempotent: still no duplicate rows / URLs.
    $this->seed(SeoSeeder::class);
    $urlsAfter = collect($this->getJson('/api/v1/seo/sitemap')->json('data.entries'))->pluck('url');
    expect($urlsAfter->count())->toBe($urls->count());
});

it('emits only valid JSON-LD from the resolver', function () {
    // A course with no stored json_ld and no derived one resolves to null (never invalid output).
    $course = Course::factory()->create();
    $resolved = app(SeoResolver::class)->resolve(SeoEntityType::Course, $course->public_id);

    expect($resolved['json_ld'])->toBeNull();

    SeoMeta::factory()->forEntity(SeoEntityType::Course, $course->public_id)->create([
        'json_ld' => ['@context' => 'https://schema.org', '@type' => 'Course', 'name' => 'X'],
    ]);

    $resolved = app(SeoResolver::class)->resolve(SeoEntityType::Course, $course->public_id);
    expect($resolved['json_ld'])->toBeArray()
        ->and($resolved['json_ld']['@type'])->toBe('Course');
});
