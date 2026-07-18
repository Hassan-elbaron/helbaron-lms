<?php

namespace App\Domains\Assessment\Actions\Assessment;

use App\Domains\Assessment\Models\Assessment;
use App\Platform\Shared\Html\HtmlSanitizer;

class UpdateAssessmentAction
{
    private const SETTINGS = [
        'title', 'passing_score', 'negative_marking', 'max_attempts', 'time_limit_seconds',
        'shuffle_questions', 'shuffle_options', 'questions_per_attempt', 'feedback_mode',
    ];

    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /** @param  array<string, mixed>  $data */
    public function execute(Assessment $assessment, array $data): Assessment
    {
        $attributes = [];

        foreach (self::SETTINGS as $key) {
            // array_key_exists, not isset: null is a meaningful value here (e.g. clearing the time
            // limit or the pass mark), and isset() would silently drop it.
            if (array_key_exists($key, $data)) {
                $attributes[$key] = $data[$key];
            }
        }

        if (array_key_exists('description', $data)) {
            $attributes['description'] = is_string($data['description'])
                ? $this->sanitizer->sanitize($data['description'])
                : null;
        }

        if ($attributes !== []) {
            $assessment->fill($attributes)->save();
        }

        return $assessment->refresh();
    }
}
