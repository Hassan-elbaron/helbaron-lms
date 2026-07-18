<?php

namespace App\Domains\Assessment\Enums;

/**
 * What an Assessment is *for*. V1 only creates `Lesson`-scoped assessments, but the column exists
 * from day one so course-level finals, placement tests, certification exams, practice quizzes and
 * reusable bank templates are additive: a new case here plus a new attach relationship, with no
 * change to the assessments/questions/options/attempts/answers schema.
 *
 * Scope describes intent, NOT placement. Placement is a relationship (today: a nullable
 * `lessons.assessment_id`), which is what keeps an Assessment reusable and independently
 * versioned rather than owned by one lesson.
 */
enum AssessmentScope: string
{
    /** Attached to a single lesson in a curriculum. The only scope V1 creates. */
    case Lesson = 'lesson';

    // ── Reserved: accepted by the schema, not yet creatable through the API ─────
    case Course = 'course';
    case Placement = 'placement';
    case Certification = 'certification';
    case Practice = 'practice';
    /** A reusable source of questions rather than something a learner sits directly. */
    case Bank = 'bank';

    /** Scopes the V1 authoring API will accept on create. */
    public function isCreatable(): bool
    {
        return $this === self::Lesson;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
