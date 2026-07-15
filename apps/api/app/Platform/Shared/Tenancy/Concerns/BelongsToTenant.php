<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy\Concerns;

use App\Platform\Shared\Tenancy\TenancyBypassPolicy;
use App\Platform\Shared\Tenancy\TenantContext;
use App\Platform\Shared\Tenancy\TenantId;
use App\Platform\Shared\Tenancy\TenantMetadata;
use App\Platform\Shared\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Opt-in tenant ownership for an Eloquent model. Applying this trait to a model makes it:
 *   - automatically FILTERED to the active tenant (via TenantScope),
 *   - automatically ASSIGNED the active tenant on create,
 * and provides tenant OWNERSHIP verification + an explicit per-tenant query scope.
 *
 * The tenant column is resolved via TenantMetadata (config/tenancy.php) or a model's own
 * `protected string $tenantColumn` — no column is hardcoded here, so new tenant shapes
 * (workspace_id, company_id, school_id, ...) need no change to this trait.
 * Composes cleanly with SoftDeletes (multiple global scopes) and is a natural hook point for
 * future auditing. Adoption is per-model and controlled — no model is scoped until it opts in.
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(static function (Model $model): void {
            if (method_exists($model, 'assignTenantOnCreate')) {
                $model->assignTenantOnCreate();
            }
        });
    }

    /** The column that stores the owning tenant id: a model override, else config-driven metadata. */
    public function getTenantColumn(): string
    {
        if (property_exists($this, 'tenantColumn')) {
            return $this->tenantColumn;
        }

        return app(TenantMetadata::class)->columnFor($this);
    }

    /** Stamp the active tenant on create when present and not already set (and not bypassed). */
    public function assignTenantOnCreate(): void
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        /** @var TenancyBypassPolicy $bypassPolicy */
        $bypassPolicy = app(TenancyBypassPolicy::class);

        if ($context->isBypassed() || $bypassPolicy->shouldBypassTenancy()) {
            return;
        }

        $tenantId = $context->id();
        $column = $this->getTenantColumn();

        if ($tenantId !== null && $this->getAttribute($column) === null) {
            $this->setAttribute($column, $tenantId->value);
        }
    }

    /** True when this record belongs to the given tenant. */
    public function belongsToTenant(TenantId $tenantId): bool
    {
        return (string) $this->getAttribute($this->getTenantColumn()) === $tenantId->toString();
    }

    /** Explicit per-tenant query scope: Model::forTenant($tenantId). */
    public function scopeForTenant(Builder $query, TenantId $tenantId): Builder
    {
        return $query->where($this->getTenantColumn(), $tenantId->value);
    }
}
