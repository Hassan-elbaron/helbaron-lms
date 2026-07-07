<?php

declare(strict_types=1);

use App\Platform\Shared\Tenancy\Contracts\CurrentTenantProvider;
use App\Platform\Shared\Tenancy\Events\TenantActivated;
use App\Platform\Shared\Tenancy\Events\TenantDomainAdded;
use App\Platform\Shared\Tenancy\Events\TenantEvent;
use App\Platform\Shared\Tenancy\Events\TenantLimitsChanged;
use App\Platform\Shared\Tenancy\Events\TenantProvisioned;
use App\Platform\Shared\Tenancy\Events\TenantSuspended;
use App\Platform\Shared\Tenancy\Lifecycle\TenantLimits;
use App\Platform\Shared\Tenancy\TenantContext;
use App\Platform\Shared\Tenancy\TenantId;

it('constructs immutable tenant event DTOs implementing TenantEvent', function (): void {
    $id = TenantId::from(42);

    $provisioned = new TenantProvisioned($id, ['plan' => 'pro']);
    $activated = new TenantActivated($id);
    $suspended = new TenantSuspended($id, 'non-payment');
    $limits = new TenantLimitsChanged($id, new TenantLimits(['max_members' => 5]));
    $domain = new TenantDomainAdded($id, 'acme.example.com');

    foreach ([$provisioned, $activated, $suspended, $limits, $domain] as $event) {
        expect($event)->toBeInstanceOf(TenantEvent::class)
            ->and($event->tenantId()->toString())->toBe('42')
            ->and($event->occurredAt())->toBeInstanceOf(DateTimeImmutable::class);
    }

    expect($provisioned->metadata['plan'])->toBe('pro')
        ->and($suspended->reason)->toBe('non-payment')
        ->and($limits->limits->limit('max_members'))->toBe(5)
        ->and($domain->domain)->toBe('acme.example.com');
});

it('binds CurrentTenantProvider to the TenantContext singleton', function (): void {
    $provider = app(CurrentTenantProvider::class);

    expect($provider)->toBeInstanceOf(TenantContext::class);

    app(TenantContext::class)->set(TenantId::from(7));

    expect($provider->hasTenant())->toBeTrue()
        ->and($provider->currentTenant()?->toString())->toBe('7');

    app(TenantContext::class)->forget();
});
