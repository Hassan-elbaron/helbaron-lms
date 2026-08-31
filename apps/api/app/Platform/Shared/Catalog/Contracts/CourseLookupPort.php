<?php

namespace App\Platform\Shared\Catalog\Contracts;

interface CourseLookupPort
{
    /**
     * Resolve a published course by public id without exposing Catalog's Eloquent model.
     *
     * @return array{id:int, public_id:string, title:string}|null
     */
    public function publishedCourseByPublicId(string $publicId): ?array;

    /**
     * The platform user ids of the people who teach this course.
     *
     * Needed by any context that has to reach a course TEAM without knowing what a course is — the
     * Q&A overdue sweep being the first: a question nobody has answered has to be escalated to
     * somebody, and "somebody" is defined by Catalog, not by Q&A.
     *
     * Empty for an unknown course, which is the same as a course with nobody to tell.
     *
     * @return list<int>
     */
    public function trainerUserIds(int $courseId): array;

    /**
     * A course's display title by internal id, or null when there is no such course. A notification
     * that says which course it is about needs this and nothing else.
     */
    public function courseTitle(int $courseId): ?string;

    /**
     * Whether the course is DECLARED free by its author, for the given ids, keyed by course id.
     *
     * Freeness is a stated intent that Catalog owns, not something inferred from the absence of a
     * product row. The inference made one `product_courses` row a one-way door: an "All Access"
     * bundle that included a free intro course un-freed it permanently, and so did a draft product
     * created by mistake. This answers only "did somebody say it is free" — whether it is currently
     * ON SALE is a separate Commerce question, and both must agree before the payment-free enrolment
     * path opens.
     *
     * Batched so a catalogue listing never issues a query per row.
     *
     * @param  list<int>  $courseIds
     * @return array<int, bool>
     */
    public function freeFlagsForCourseIds(array $courseIds): array;
}
