<?php

use App\Domains\Authoring\Enums\LessonType;

it('lists supported lesson types and media usage', function () {
    expect(LessonType::values())->toEqual(['video', 'audio', 'article', 'pdf', 'download', 'external_link', 'quiz_placeholder', 'quiz'])
        ->and(LessonType::Video->usesMedia())->toBeTrue()
        ->and(LessonType::Audio->usesMedia())->toBeTrue()
        ->and(LessonType::Audio->label())->toBe('Audio')
        ->and(LessonType::ExternalLink->usesMedia())->toBeFalse()
        ->and(LessonType::Pdf->label())->toBe('PDF');
});

it('separates assessment-backed quizzes from the inert placeholder', function () {
    // `quiz` is driven by an attached Assessment record; `quiz_placeholder` is authored text only.
    expect(LessonType::Quiz->usesAssessment())->toBeTrue()
        ->and(LessonType::QuizPlaceholder->usesAssessment())->toBeFalse()
        ->and(LessonType::Quiz->usesMedia())->toBeFalse()
        ->and(LessonType::Quiz->label())->toBe('Quiz');
});
