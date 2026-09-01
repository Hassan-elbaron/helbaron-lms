<?php

use App\Platform\Branding\Models\BrandSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Brand assertions here compare against BrandSetting::defaults() rather than a literal brand string.
 *
 * They previously pinned the vendor's name ('HElbaron' / 'إلبارون'). That is a hardcoded brand
 * literal in a product whose whole selling model is one white-labelled instance per customer, so the
 * tests broke the moment the defaults became env-driven — and any assertion that passed did so only
 * because this machine's APP_NAME happened to match, which is not true on a customer instance.
 * What is actually under test is the MERGE: a stored value wins, an unset sibling inherits the
 * default, and no default is ever empty.
 */

it('current() creates and returns the singleton row', function () {
    expect(BrandSetting::query()->count())->toBe(0);

    $first = BrandSetting::current();
    $second = BrandSetting::current();

    expect(BrandSetting::query()->count())->toBe(1)
        ->and($first->id)->toBe($second->id)
        ->and($first->public_id)->not->toBeNull();
});

it('toPublicArray merges stored values over the built-in defaults', function () {
    $setting = BrandSetting::current();
    $setting->update(['identity' => ['brand_name' => ['en' => 'Acme']]]);

    $payload = $setting->fresh()->toPublicArray();

    $defaults = BrandSetting::defaults();

    // Overridden value wins, unset siblings keep defaults (full set guaranteed).
    expect($payload['identity']['brand_name']['en'])->toBe('Acme')
        ->and($payload['identity']['brand_name']['ar'])->toBe($defaults['identity']['brand_name']['ar'])
        ->and($payload['identity']['currency'])->toBe($defaults['identity']['currency'])
        ->and($payload['theme']['colors']['primary'])->toBe($defaults['theme']['colors']['primary'])
        // A default that resolves to '' is the failure this pins down: it is what shipped when the
        // BRAND_* keys were present-but-empty in .env.
        ->and(trim($payload['identity']['brand_name']['ar']))->not->toBe('')
        ->and(trim($payload['identity']['currency']))->not->toBe('');
});

it('GET /api/v1/branding returns the public payload with brand name and merged theme colours', function () {
    $res = $this->getJson('/api/v1/branding')->assertOk();

    $defaults = BrandSetting::defaults();

    expect($res->json('data.identity.brand_name.en'))->toBe($defaults['identity']['brand_name']['en'])
        ->and(trim((string) $res->json('data.identity.brand_name.en')))->not->toBe('')
        ->and($res->json('data.theme.colors.primary'))->toBe('oklch(0.36 0.045 185)')
        ->and($res->json('data.theme.radius'))->toBe('0.75rem')
        ->and($res->json('data.theme.dark.primary'))->toBe('oklch(0.62 0.07 183)')
        ->and($res->json('data.logos'))->toBeArray()
        ->and($res->json('data.certificate.qr_position'))->toBe('bottom-right');
});

it('reflects an admin theme colour update on the public endpoint', function () {
    $setting = BrandSetting::current();
    $setting->update(['theme' => ['colors' => ['primary' => '#ff0000']]]);

    $this->getJson('/api/v1/branding')
        ->assertOk()
        ->assertJsonPath('data.theme.colors.primary', '#ff0000')
        // A sibling colour the admin did not touch still falls back to the default.
        ->assertJsonPath('data.theme.colors.secondary', 'oklch(0.91 0.03 86)');
});

it('serves the branding endpoint publicly (no authentication required)', function () {
    $this->getJson('/api/v1/branding')->assertOk();
});
