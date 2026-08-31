<?php

use App\Platform\Identity\Models\User;
use App\Platform\Notifications\Models\UserNotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * E2 — the digest setting is withdrawn, not quietly broken.
 *
 * `digest_frequency` validated, persisted, came back from the API and was documented in the OpenAPI
 * spec. A user could select "daily" and nothing would ever send one: `DigestService` has zero
 * callers, there is no scheduled command, and there is no delivery path at all.
 *
 * The decision was to remove the control rather than wire it. Wiring a digest properly means a
 * scheduler entry, per-user timezone windows, a dedup ledger so a retry or a mid-run deploy cannot
 * send twice, retry handling, bilingual templates, operator controls and an audit trail — a feature,
 * not a fix. An honest absence beats a control that does nothing.
 *
 * These tests hold the withdrawal in place AND prove it is one config flag from coming back, so it
 * cannot rot into a permanent deletion by accident.
 */
beforeEach(function (): void {
    $this->user = User::factory()->create(['email_verified_at' => now()]);
    $this->actingAs($this->user, 'sanctum');
});

it('refuses a digest preference rather than pretending to save it', function (): void {
    // 422, not a silent ignore. A client that sends it is told, instead of being left believing a
    // preference was stored.
    // The API renders validation failures through its own envelope (BaseFormRequest::failedValidation
    // -> error.details.fields), not Laravel's default `errors` bag, so assertJsonValidationErrors
    // does not apply here.
    $this->postJson('/api/v1/notifications/preferences', ['digest_frequency' => 'daily'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['details' => ['fields' => ['digest_frequency']]]]);
});

it('does not offer the field in its response', function (): void {
    $response = $this->postJson('/api/v1/notifications/preferences', ['locale' => 'en'])->assertOk();

    // The frontend renders the control from the presence of this key, so omitting it here is what
    // makes the withdrawal single-switched rather than something to remember in two places.
    expect($response->json('data'))->not->toHaveKey('digest_frequency');
});

it('still saves every preference that does work', function (): void {
    // The withdrawal must not take the working settings with it.
    $this->postJson('/api/v1/notifications/preferences', [
        'locale' => 'ar',
        'timezone' => 'Asia/Riyadh',
        'quiet_hours_enabled' => true,
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
    ])
        ->assertOk()
        ->assertJsonPath('data.locale', 'ar')
        ->assertJsonPath('data.timezone', 'Asia/Riyadh')
        ->assertJsonPath('data.quiet_hours_enabled', true);
});

/*
 * The other direction. Without this, "withdrawn" would be indistinguishable from "deleted", and
 * whoever eventually builds the delivery side would have to rediscover how to switch it on.
 */
it('comes back with one config flag once delivery exists', function (): void {
    config(['notifications.digest.enabled' => true]);

    $response = $this->postJson('/api/v1/notifications/preferences', ['digest_frequency' => 'weekly'])
        ->assertOk();

    expect($response->json('data.digest_frequency'))->toBe('weekly');
});

it('keeps the stored column intact while withdrawn', function (): void {
    // Nobody's previously-saved choice is destroyed by hiding the control.
    config(['notifications.digest.enabled' => true]);
    $this->postJson('/api/v1/notifications/preferences', ['digest_frequency' => 'weekly'])->assertOk();

    config(['notifications.digest.enabled' => false]);
    $this->postJson('/api/v1/notifications/preferences', ['locale' => 'en'])->assertOk();

    expect(UserNotificationSetting::where('user_id', $this->user->id)->first()->digest_frequency->value)
        ->toBe('weekly');
});
