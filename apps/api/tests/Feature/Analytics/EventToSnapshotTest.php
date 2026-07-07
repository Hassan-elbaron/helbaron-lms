<?php

use App\Contexts\Commerce\Events\OrderPaid;
use App\Contexts\Commerce\Models\Order;
use App\Platform\Identity\Events\UserRegistered;
use App\Platform\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('turns producer events into read-model snapshots surfaced as KPIs', function () {
    // Producer events (Analytics only listens to EVENTS, never their tables).
    UserRegistered::dispatch(User::factory()->create());
    UserRegistered::dispatch(User::factory()->create());
    OrderPaid::dispatch(Order::factory()->create(['total_minor' => 15000]));

    Sanctum::actingAs(User::factory()->create());

    $res = $this->getJson('/api/v1/analytics/kpis?metrics[]=signups&metrics[]=revenue')->assertOk();

    $kpis = collect($res->json('data.kpis'))->keyBy('metric');
    expect($kpis['signups']['total'])->toBe(2)
        ->and($kpis['revenue']['total'])->toBe(15000)
        ->and($kpis['revenue']['unit'])->toBe('currency_minor');
});
