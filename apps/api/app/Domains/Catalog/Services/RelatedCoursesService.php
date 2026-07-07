<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Finds courses related to a given one (shared category or tag), published + public, excluding
 * the source course.
 */
class RelatedCoursesService extends BaseService
{
    public function for(Course $course, ?int $limit = null): Collection
    {
        $limit ??= (int) config('catalog.related.limit', 8);

        $categoryIds = $course->categories()->pluck('categories.id');
        $tagIds = $course->tags()->pluck('course_tags.id');

        return Course::query()
            ->published()
            ->visible()
            ->whereKeyNot($course->id)
            ->where(function (Builder $q) use ($categoryIds, $tagIds): void {
                $q->whereHas('categories', fn (Builder $c) => $c->whereIn('categories.id', $categoryIds))
                    ->orWhereHas('tags', fn (Builder $t) => $t->whereIn('course_tags.id', $tagIds));
            })
            ->with(['level', 'language'])
            ->limit($limit)
            ->get();
    }
}
