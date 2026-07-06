<?php

use App\Domains\Commerce\Models\Contract;
use App\Domains\Commerce\Models\ContractTemplate;
use App\Domains\Commerce\Models\Order;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('records an immutable acceptance with version + body hash', function () {
    $template = ContractTemplate::factory()->create(['version' => 2, 'body' => 'Custom terms body']);
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $contract = Contract::create(['user_id' => $user->id, 'order_id' => $order->id, 'template_id' => $template->id, 'status' => 'pending']);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/contracts/{$contract->public_id}/accept")->assertOk()->assertJsonPath('data.status', 'accepted');

    $acceptance = $contract->fresh()->acceptances()->firstOrFail();
    expect($acceptance->template_version)->toBe(2)
        ->and($acceptance->body_hash)->toBe(hash('sha256', 'Custom terms body'));

    // Second accept is rejected.
    $this->postJson("/api/v1/contracts/{$contract->public_id}/accept")
        ->assertStatus(409)->assertJsonPath('error.code', 'COMMERCE_CONTRACT_ALREADY_ACCEPTED');
});
