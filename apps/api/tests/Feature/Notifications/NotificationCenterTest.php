<?php

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Models\UserNotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists notifications (unread first), shows, and marks read', function () {
    $user = User::factory()->create();
    Notification::factory()->read()->create(['user_id' => $user->id, 'title' => 'Old']);
    $unread = Notification::factory()->create(['user_id' => $user->id, 'title' => 'New']);
    Notification::factory()->create(['user_id' => User::factory()]); // other user

    Sanctum::actingAs($user);

    $list = $this->getJson('/api/v1/notifications')->assertOk();
    expect($list->json('meta.total'))->toBe(2)
        ->and($list->json('data.0.title'))->toBe('New'); // unread first

    $this->getJson("/api/v1/notifications/{$unread->public_id}")->assertOk()->assertJsonPath('data.read', false);
    $this->postJson("/api/v1/notifications/{$unread->public_id}/read")->assertOk()->assertJsonPath('data.read', true);
});

it('updates notification preferences', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/notifications/preferences', [
        'locale' => 'ar',
        'digest_frequency' => 'daily',
        'preferences' => [['category' => 'commerce', 'channel' => 'email', 'enabled' => false]],
    ])->assertOk()->assertJsonPath('data.locale', 'ar');

    expect(UserNotificationSetting::where('user_id', $user->id)->first()->digest_frequency->value)->toBe('daily')
        ->and($user->fresh())->not->toBeNull();
});

it('forbids viewing another user notification', function () {
    $notification = Notification::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/notifications/{$notification->public_id}")->assertStatus(403);
});
