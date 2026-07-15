<?php

use App\Platform\Branding\Models\BrandSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

    // Overridden value wins, unset siblings keep defaults (full set guaranteed).
    expect($payload['identity']['brand_name']['en'])->toBe('Acme')
        ->and($payload['identity']['brand_name']['ar'])->toBe('إلبارون')
        ->and($payload['identity']['currency'])->toBe('SAR')
        ->and($payload['theme']['colors']['primary'])->toBe('oklch(0.36 0.045 185)');
});

it('GET /api/v1/branding returns the public payload with brand name and merged theme colours', function () {
    $res = $this->getJson('/api/v1/branding')->assertOk();

    expect($res->json('data.identity.brand_name.en'))->toBe('HElbaron')
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
