<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Models\Category;
use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Models\CourseLanguage;
use App\Domains\Catalog\Models\CourseLevel;
use App\Domains\Catalog\Models\CourseTag;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the public course listing query from filters (all keyed by public_id) with search,
 * featured ordering and pagination. Read-only; only ever returns published + public courses.
 */
class CourseSearchService extends BaseService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Course::query()
            ->published()
            ->visible()
            ->with(['level', 'language', 'categories', 'tags', 'trainers']);

        $this->applyCategory($query, $filters['category'] ?? null);
        $this->applyLevel($query, $filters['level'] ?? null);
        $this->applyLanguage($query, $filters['language'] ?? null);
        $this->applyTag($query, $filters['tag'] ?? null);
        $this->applyFeatured($query, $filters['featured'] ?? null);
        $this->applySearch($query, $filters['q'] ?? null);

        return $query
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function applyCategory(Builder $query, ?string $publicId): void
    {
        if ($publicId) {
            $id = Category::where('public_id', $publicId)->value('id');
            $query->whereHas('categories', fn (Builder $q) => $q->whereKey($id));
        }
    }

    private function applyLevel(Builder $query, ?string $publicId): void
    {
        if ($publicId) {
            $query->where('level_id', CourseLevel::where('public_id', $publicId)->value('id'));
        }
    }

    private function applyLanguage(Builder $query, ?string $publicId): void
    {
        if ($publicId) {
            $query->where('language_id', CourseLanguage::where('public_id', $publicId)->value('id'));
        }
    }

    private function applyTag(Builder $query, ?string $publicId): void
    {
        if ($publicId) {
            $id = CourseTag::where('public_id', $publicId)->value('id');
            $query->whereHas('tags', fn (Builder $q) => $q->whereKey($id));
        }
    }

    private function applyFeatured(Builder $query, mixed $featured): void
    {
        if ($featured !== null && filter_var($featured, FILTER_VALIDATE_BOOLEAN)) {
            $query->featured();
        }
    }

    private function applySearch(Builder $query, ?string $term): void
    {
        $term = is_string($term) ? trim($term) : '';

        if (strlen($term) >= (int) config('catalog.search.min_query_length', 2)) {
            $query->where(function (Builder $q) use ($term): void {
                $q->where('title', 'ilike', "%{$term}%")
                    ->orWhere('subtitle', 'ilike', "%{$term}%");
            });
        }
    }
}
