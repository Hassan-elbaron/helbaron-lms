<?php

use App\Domains\Authoring\Enums\LessonType;

it('lists supported lesson types and media usage', function () {
    expect(LessonType::values())->toEqual(['video', 'audio', 'article', 'pdf', 'download', 'external_link', 'quiz_placeholder'])
        ->and(LessonType::Video->usesMedia())->toBeTrue()
        ->and(LessonType::Audio->usesMedia())->toBeTrue()
        ->and(LessonType::Audio->label())->toBe('Audio')
        ->and(LessonType::ExternalLink->usesMedia())->toBeFalse()
        ->and(LessonType::Pdf->label())->toBe('PDF');
});
