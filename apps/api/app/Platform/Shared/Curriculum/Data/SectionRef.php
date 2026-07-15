<?php

namespace App\Platform\Shared\Curriculum\Data;

/**
 * Immutable, read-only reference to a curriculum section, carrying only the fields Learning
 * renders. No Eloquent. Produced by the CurriculumReadPort from a loaded model.
 */
final readonly class SectionRef
{
    public function __construct(
        public int $id,
        public string $publicId,
        public string $title,
    ) {}
}
