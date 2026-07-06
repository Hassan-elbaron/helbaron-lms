<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Models\Lead;
use App\Shared\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Search/filter for CRM leads (extendable to contacts/companies). No AI, no external services.
 */
class CrmSearchService extends BaseService
{
    /** @param array<string, mixed> $filters */
    public function leads(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Lead::query()->with(['stage', 'owner']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $term = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if (strlen($term) >= (int) config('crm.search.min_query_length', 2)) {
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'ilike', "%{$term}%")
                    ->orWhere('email', 'ilike', "%{$term}%");
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
