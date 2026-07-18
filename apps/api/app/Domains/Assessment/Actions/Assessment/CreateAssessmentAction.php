<?php

namespace App\Domains\Assessment\Actions\Assessment;

use App\Domains\Assessment\Enums\AssessmentScope;
use App\Domains\Assessment\Enums\AssessmentStatus;
use App\Domains\Assessment\Models\Assessment;
use App\Platform\Shared\Html\HtmlSanitizer;

class CreateAssessmentAction
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /** @param  array<string, mixed>  $data */
    public function execute(int $courseId, ?int $createdBy, array $data): Assessment
    {
        return Assessment::create([
            'course_id' => $courseId,
            'title' => $data['title'],
            // Author-supplied rich text is sanitized on the way IN, never on the way out.
            'description' => isset($data['description']) && is_string($data['description'])
                ? $this->sanitizer->sanitize($data['description'])
                : null,
            // V1 creates lesson-scoped assessments only; other scopes exist in the schema but are
            // not yet reachable through the API.
            'scope' => AssessmentScope::Lesson->value,
            // Never created published: a brand-new assessment has no questions and could not pass
            // the publish guard anyway.
            'status' => AssessmentStatus::Draft->value,
            'passing_score' => $data['passing_score'] ?? null,
            'negative_marking' => $data['negative_marking'] ?? false,
            'max_attempts' => $data['max_attempts'] ?? null,
            'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
            'shuffle_questions' => $data['shuffle_questions'] ?? false,
            'shuffle_options' => $data['shuffle_options'] ?? false,
            'questions_per_attempt' => $data['questions_per_attempt'] ?? null,
            'feedback_mode' => $data['feedback_mode'] ?? 'after_submit',
            'version' => 1,
            'created_by' => $createdBy,
        ]);
    }
}
