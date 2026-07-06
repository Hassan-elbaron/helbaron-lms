<?php

namespace App\Domains\Certification\Enums;

enum BadgeSource: string
{
    case CourseCompletion = 'course_completion';
    case Manual = 'manual';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
