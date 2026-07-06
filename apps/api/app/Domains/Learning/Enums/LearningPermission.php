<?php

namespace App\Domains\Learning\Enums;

enum LearningPermission: string
{
    case ViewOwnLearning = 'learning.self.view';
    case ManageEnrollments = 'learning.enrollments.manage';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
