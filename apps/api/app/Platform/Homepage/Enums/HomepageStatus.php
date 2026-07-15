<?php

namespace App\Platform\Homepage\Enums;

/**
 * Editorial lifecycle of a homepage block. Only `Published` blocks (and only while inside their
 * scheduled published_at/unpublished_at window) are served on the public homepage; every other
 * state is admin/preview only. The scope HomepageSection::published() combines this status with the
 * schedule window. Mirrors App\Platform\Pages\Enums\PageStatus, adding Review/Approved stages.
 *
 * Existing blocks default to Published on migration so current public behavior is preserved.
 */
enum HomepageStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'In review',
            self::Approved => 'Approved',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    /** @return array<string, string> value => label, for Filament selects. */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
