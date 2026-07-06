<?php

namespace App\Domains\Authoring\Enums;

enum PublishState: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
