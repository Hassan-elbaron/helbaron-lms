<?php

declare(strict_types=1);

namespace App\Platform\Shared\Tenancy;

/**
 * Tenancy configuration metadata: which database column identifies the owning tenant.
 *
 * Removes the hardcoded default from BelongsToTenant/TenantScope. The default column and any
 * per-model overrides come from config (config/tenancy.php). A model can also declare its own
 * `protected string $tenantColumn` (read by the trait). New tenant columns (tenant_id,
 * organization_id, workspace_id, company_id, school_id, ...) are enabled by config/override —
 * WITHOUT modifying BelongsToTenant.
 */
final class TenantMetadata
{
    /**
     * @param  list<string>  $supportedColumns
     * @param  array<class-string, string>  $overrides
     */
    public function __construct(
        private readonly string $defaultColumn,
        private readonly array $supportedColumns,
        private readonly array $overrides = [],
    ) {
    }

    /** The tenant column for a model: a config override for its class, else the default. */
    public function columnFor(object $model): string
    {
        return $this->overrides[$model::class] ?? $this->defaultColumn;
    }

    public function defaultColumn(): string
    {
        return $this->defaultColumn;
    }

    /** @return list<string> */
    public function supportedColumns(): array
    {
        return $this->supportedColumns;
    }

    public function supports(string $column): bool
    {
        return in_array($column, $this->supportedColumns, true);
    }
}
