<?php

use App\Domains\Crm\Models\ConsultingRequest;
use App\Domains\Crm\Services\ConsultingSlaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes the SLA due time and detects breaches', function () {
    config(['crm.consulting.sla_hours' => 24]);
    $svc = new ConsultingSlaService;

    $due = $svc->dueAt(CarbonImmutable::parse('2025-01-01 00:00'));
    expect($due->toDateTimeString())->toBe('2025-01-02 00:00:00');

    $breached = ConsultingRequest::factory()->create(['sla_due_at' => now()->subHour(), 'status' => 'new']);
    $onTime = ConsultingRequest::factory()->create(['sla_due_at' => now()->addHour(), 'status' => 'new']);

    expect($svc->isBreached($breached))->toBeTrue()
        ->and($svc->isBreached($onTime))->toBeFalse();
});
