<?php

use App\Domains\Crm\Models\ConsultingRequest;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('submits a consulting request with an SLA and lists own requests', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $res = $this->postJson('/api/v1/consulting/request', ['subject' => 'Need help onboarding', 'description' => 'Details'])
        ->assertCreated();

    expect($res->json('data.subject'))->toBe('Need help onboarding')
        ->and($res->json('data.sla_due_at'))->toBeString();

    $request = ConsultingRequest::where('public_id', $res->json('data.id'))->firstOrFail();
    expect($request->activities()->count())->toBe(1); // ConsultingRequestCreated -> ActivityLogger

    $this->getJson('/api/v1/consulting')->assertOk()->assertJsonCount(1, 'data');
});
