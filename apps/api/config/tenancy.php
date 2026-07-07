<?php

declare(strict_types=1);

/*
 | Tenancy configuration (Sprint 1 / A2-S04).
 |
 | Controls how the owning tenant is identified on tenant-scoped models. Adding support for a new
 | tenant column, or pointing a specific model at a different column, is done HERE (or via a model's
 | `protected string $tenantColumn`) — never by editing the BelongsToTenant trait.
 */

return [
    // The default tenant column used by BelongsToTenant models that do not override it.
    'default_column' => env('TENANCY_DEFAULT_COLUMN', 'organization_id'),

    // Tenant columns the platform recognises. Future-proofing for other tenancy shapes.
    'columns' => [
        'organization_id',
        'tenant_id',
        'workspace_id',
        'company_id',
        'school_id',
    ],

    // Per-model column overrides: [\Fully\Qualified\Model::class => 'workspace_id'].
    'overrides' => [
        // e.g. \App\Domains\SomeContext\Models\Thing::class => 'workspace_id',
    ],

    // Default per-tenant resource limits (foundation only; enforcement is a later story).
    // Null = unlimited. Keys are illustrative and consumed by TenantLimits.
    'default_limits' => [
        'max_members' => null,
        'max_courses' => null,
        'max_storage_mb' => null,
        'max_seats' => null,
    ],
];
