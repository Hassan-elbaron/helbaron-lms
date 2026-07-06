<?php

namespace App\Domains\Catalog\Enums;

enum CatalogPermission: string
{
    case ViewCourses = 'catalog.courses.view';
    case ManageCourses = 'catalog.courses.manage';
    case ManageCategories = 'catalog.categories.manage';
    case ManageTaxonomy = 'catalog.taxonomy.manage';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
