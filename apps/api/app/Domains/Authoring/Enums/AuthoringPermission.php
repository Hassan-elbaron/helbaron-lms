<?php

namespace App\Domains\Authoring\Enums;

enum AuthoringPermission: string
{
    case ViewCurriculum = 'authoring.curriculum.view';
    case ManageCurriculum = 'authoring.curriculum.manage';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
