<?php

use App\Domains\Catalog\Enums\CourseStatus;

it('exposes course status helpers', function () {
    expect(CourseStatus::Published->isPublished())->toBeTrue()
        ->and(CourseStatus::Draft->isPublished())->toBeFalse()
        ->and(CourseStatus::values())->toContain('draft', 'published', 'archived');
});
