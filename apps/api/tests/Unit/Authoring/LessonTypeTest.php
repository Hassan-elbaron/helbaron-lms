<?php

use App\Domains\Authoring\Enums\LessonType;

it('lists supported lesson types and media usage', function () {
    expect(LessonType::values())->toEqual(['video', 'article', 'pdf', 'download', 'external_link', 'quiz_placeholder'])
        ->and(LessonType::Video->usesMedia())->toBeTrue()
        ->and(LessonType::ExternalLink->usesMedia())->toBeFalse()
        ->and(LessonType::Pdf->label())->toBe('PDF');
});
