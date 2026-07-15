<?php

declare(strict_types=1);

use App\Platform\Shared\Tenancy\Lifecycle\Tenant;
use App\Platform\Shared\Tenancy\Lifecycle\TenantBranding;
use App\Platform\Shared\Tenancy\Lifecycle\TenantDomains;
use App\Platform\Shared\Tenancy\Lifecycle\TenantLimits;
use App\Platform\Shared\Tenancy\Lifecycle\TenantSettings;
use App\Platform\Shared\Tenancy\Lifecycle\TenantStatus;
use App\Platform\Shared\Tenancy\Lifecycle\TenantUsage;
use App\Platform\Shared\Tenancy\NullTenancyBypassPolicy;
use App\Platform\Shared\Tenancy\TenantId;
use App\Platform\Shared\Tenancy\TenantMetadata;

it('enforces the tenant lifecycle state machine', function (): void {
    expect(TenantStatus::Provisioning->canTransitionTo(TenantStatus::Active))->toBeTrue()
        ->and(TenantStatus::Active->canTransitionTo(TenantStatus::Suspended))->toBeTrue()
        ->and(TenantStatus::Suspended->canTransitionTo(TenantStatus::Active))->toBeTrue()
        ->and(TenantStatus::Archived->canTransitionTo(TenantStatus::Active))->toBeTrue()
        ->and(TenantStatus::Provisioning->canTransitionTo(TenantStatus::Suspended))->toBeFalse()
        ->and(TenantStatus::Archived->canTransitionTo(TenantStatus::Suspended))->toBeFalse();
});

it('resolves tenant columns from metadata (default + override), no hardcoded default', function (): void {
    $metadata = new TenantMetadata('organization_id', ['organization_id', 'workspace_id'], [
        stdClass::class => 'workspace_id',
    ]);

    expect($metadata->defaultColumn())->toBe('organization_id')
        ->and($metadata->columnFor(new stdClass))->toBe('workspace_id')
        ->and($metadata->columnFor(new Exception))->toBe('organization_id') // falls back to default
        ->and($metadata->supports('school_id'))->toBeFalse()
        ->and($metadata->supports('workspace_id'))->toBeTrue();
});

it('defaults to no bypass', function (): void {
    expect((new NullTenancyBypassPolicy)->shouldBypassTenancy())->toBeFalse();
});

it('models tenant limits and usage', function (): void {
    $limits = new TenantLimits(['max_members' => 5, 'max_courses' => null]);

    expect($limits->isUnlimited('max_courses'))->toBeTrue()
        ->and($limits->exceeds('max_members', 6))->toBeTrue()
        ->and($limits->exceeds('max_members', 5))->toBeFalse();

    $within = new TenantUsage(['max_members' => 5]);
    $over = new TenantUsage(['max_members' => 6]);

    expect($within->within($limits))->toBeTrue()
        ->and($over->within($limits))->toBeFalse();
});

it('composes an immutable tenant descriptor and transitions status', function (): void {
    $tenant = new Tenant(
        TenantId::from(1),
        TenantStatus::Provisioning,
        new TenantSettings(['locale' => 'ar']),
        new TenantLimits(['max_members' => 10]),
        new TenantUsage(['max_members' => 3]),
        new TenantBranding(displayName: 'Acme'),
        new TenantDomains(primary: 'acme.example.com', aliases: ['acme.io']),
        ['plan' => 'enterprise'],
    );

    expect($tenant->isActive())->toBeFalse()
        ->and($tenant->settings->get('locale'))->toBe('ar')
        ->and($tenant->domains->matches('acme.io'))->toBeTrue()
        ->and($tenant->metadata['plan'])->toBe('enterprise');

    $active = $tenant->withStatus(TenantStatus::Active);

    expect($active->isActive())->toBeTrue()
        ->and($tenant->isActive())->toBeFalse(); // original unchanged (immutable)
});
