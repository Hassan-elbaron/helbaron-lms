<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Models\Course;
use App\Domains\Catalog\Repositories\PublicCourseRepository;
use App\Platform\Shared\Commerce\Contracts\PurchaseSummaryPort;
use App\Platform\Shared\Services\BaseService;

/** Composes the public course aggregate with related courses and its commerce projection. */
final class PublicCourseDetailsService extends BaseService
{
    public function __construct(
        private readonly PublicCourseRepository $courses,
        private readonly RelatedCoursesService $related,
        private readonly PurchaseSummaryPort $purchases,
    ) {}

    public function find(string $identifier): ?Course
    {
        $course = $this->courses->findByIdentifier($identifier);

        if ($course === null) {
            return null;
        }

        $related = $this->related->for($course);

        // Related cards render from the same purchase summary as the main panel, so without one they
        // fall through to the fail-closed default and every cross-sell card reads "Not available
        // yet" — including courses that are perfectly buyable. Resolved in ONE batched call rather
        // than per card.
        $summaries = $this->purchases->forCourseIds(
            $related->map(fn (Course $c): int => (int) $c->getKey())->all(),
        );

        foreach ($related as $relatedCourse) {
            $relatedCourse->setAttribute(
                'purchase_summary',
                $summaries[(int) $relatedCourse->getKey()] ?? null,
            );
        }

        $course->setRelation('related', $related);
        $course->setAttribute('purchase_summary', $this->purchases->forCourse((int) $course->id));

        return $course;
    }
}
