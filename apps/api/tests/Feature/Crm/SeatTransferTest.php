<?php

use App\Domains\Crm\Exceptions\SeatPoolExhaustedException;
use App\Domains\Crm\Models\Organization;
use App\Domains\Crm\Models\OrganizationMember;
use App\Domains\Crm\Models\SeatPool;
use App\Domains\Crm\Services\SeatService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assigns, transfers, and keeps seat usage consistent', function () {
    $org = Organization::factory()->create();
    $pool = SeatPool::create(['organization_id' => $org->id, 'name' => 'Team', 'total_seats' => 2, 'used_seats' => 0]);
    $a = OrganizationMember::create(['organization_id' => $org->id, 'email' => 'a@corp.com', 'role' => 'member', 'status' => 'active']);
    $b = OrganizationMember::create(['organization_id' => $org->id, 'email' => 'b@corp.com', 'role' => 'member', 'status' => 'active']);

    $service = app(SeatService::class);

    $service->assign($pool, $a);
    expect($pool->fresh()->used_seats)->toBe(1);

    $service->transfer($pool, $a, $b);

    expect($pool->fresh()->used_seats)->toBe(1) // unchanged: one revoked, one assigned
        ->and($pool->assignments()->where('member_id', $a->id)->whereNull('revoked_at')->exists())->toBeFalse()
        ->and($pool->assignments()->where('member_id', $b->id)->whereNull('revoked_at')->exists())->toBeTrue();
});

it('rejects assigning beyond capacity', function () {
    $org = Organization::factory()->create();
    $pool = SeatPool::create(['organization_id' => $org->id, 'name' => 'Solo', 'total_seats' => 1, 'used_seats' => 0]);
    $a = OrganizationMember::create(['organization_id' => $org->id, 'email' => 'a@corp.com', 'role' => 'member', 'status' => 'active']);
    $b = OrganizationMember::create(['organization_id' => $org->id, 'email' => 'b@corp.com', 'role' => 'member', 'status' => 'active']);

    app(SeatService::class)->assign($pool, $a);

    expect(fn () => app(SeatService::class)->assign($pool, $b))
        ->toThrow(SeatPoolExhaustedException::class);
});
