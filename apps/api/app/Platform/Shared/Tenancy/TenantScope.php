<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that transparently filters a tenant-owned model to the active tenant.
 *
 * It filters ONLY when a tenant is resolved AND tenancy is not bypassed AND the app is not in
 * maintenance mode. When no tenant is resolved (public/unauthenticated requests, console/queue
 * without an explicit tenant), it does nothing — so it is backward compatible and safe to add.
 *
 * Removable per query via `Model::withoutGlobalScope(TenantScope::class)`.
 */
final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        if ($context->isBypassed()) {
            return;
        }

        /** @var TenancyBypassPolicy $bypassPolicy */
        $bypassPolicy = app(TenancyBypassPolicy::class);
        if ($bypassPolicy->shouldBypassTenancy()) {
            return;
        }

        if (app()->isDownForMaintenance()) {
            return;
        }

        $tenantId = $context->id();

        if ($tenantId === null) {
            return;
        }

        $column = method_exists($model, 'g