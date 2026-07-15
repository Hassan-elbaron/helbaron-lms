<?php

namespace App\Domains\Authoring\Actions\Lesson;

use App\Domains\Authoring\Models\Lesson;
use App\Platform\Shared\Actions\BaseAction;
use App\Platform\Shared\Html\HtmlSanitizer;

class UpdateLessonAction extends BaseAction
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /** @param array<string, mixed> $data */
    public function execute(Lesson $lesson, array $data): Lesson
    {
        $content = $data['content'] ?? null;
        if (is_array($content)) {
            // Defense in depth: HTML-bearing content fields are sanitized before persistence.
            $content = $this->sanitizer->sanitizeArray($content);
        }

        return $this->transaction(function () use ($lesson, $data, $content): Lesson {
            $lesson->fill(array_filter([
                'title' => $data['title'] ?? null,
                'type' => $data['type'] ?? null,
                'content' => $content,
            ], fn ($v) => $v !== null));
            $lesson->save();

            return $lesson;
        });
    }
}
