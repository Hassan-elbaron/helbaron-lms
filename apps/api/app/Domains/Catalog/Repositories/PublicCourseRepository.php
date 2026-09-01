<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Repositories;

use App\Domains\Catalog\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Read repository for a public course detail route keyed by UUID, its slug, or a retired slug. */
final class PublicCourseRepository
{
    public function findByIdentifier(string $identifier): ?Course
    {
        /** @var Course|null $course */
        $course = $this->query()
            ->where(Str::isUuid($identifier) ? 'public_id' : 'slug', $identifier)
            ->first();

        if ($course !== null || Str::isUuid($identifier)) {
            return $course;
        }

        return $this->findByRetiredSlug($identifier);
    }

    /**
     * A slug this course used to have.
     *
     * A course slug is the primary public URL, so renaming a course used to 404 every inbound link,
     * every share and every indexed search result at once. `slug_history` keeps the old ones and this
     * resolves them, which is what lets the caller answer with a permanent redirect to the canonical
     * URL instead of a dead end. Retired slugs are never reissued to another course (see
     * HasSlug::slugIsTaken), so this can only ever return the original owner.
     */
    private function findByRetiredSlug(string $slug): ?Course
    {
        $id = DB::table('slug_history')
            ->where('sluggable_type', Course::class)
            ->where('slug', $slug)
            ->orderByDesc('id')
            ->value('sluggable_id');

        if ($id === null) {
            return null;
        }

        /** @var Course|null $course */
        $course = $this->query()->whereKey($id)->first();

        return $course;
    }

    /** @return Builder<Course> */
    private function query(): Builder
    {
        return Course::query()
            ->published()
            ->visible()
            ->with(['level', 'language', 'categories', 'tags', 'trainerLinks']);
    }
}
