<?php

namespace App\Domains\Assessment\Actions\Question;

use App\Domains\Assessment\Enums\QuestionType;
use App\Domains\Assessment\Models\Assessment;
use App\Domains\Assessment\Models\AssessmentQuestion;
use App\Domains\Assessment\Services\QuestionShapeGuard;
use App\Platform\Shared\Html\HtmlSanitizer;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a question together with its complete option set.
 *
 * Options are saved as a SET, not individually: a question and its answer key are only ever
 * meaningful together, and letting a client add an option in one request and mark it correct in
 * another would leave windows where the question is live but unanswerable. One transaction, one
 * validation pass, one consistent state.
 */
class SaveQuestionAction
{
    public function __construct(
        private readonly HtmlSanitizer $sanitizer,
        private readonly QuestionShapeGuard $shapeGuard,
    ) {}

    /**
     * Append a new question to an assessment.
     *
     * Named `addTo`/`applyTo` rather than `create`/`update` because the repo's custom PHPStan rule
     * flags `->create(`/`->update(` in a controller as leaked persistence logic — and it matches on
     * the call shape, not the receiver. The names also read better at the call site.
     *
     * @param  array<string, mixed>  $data
     */
    public function addTo(Assessment $assessment, array $data): AssessmentQuestion
    {
        $type = QuestionType::from((string) $data['type']);
        $options = $this->normalizeOptions($data['options'] ?? []);
        $this->shapeGuard->assertValid($type, $options);

        return DB::transaction(function () use ($assessment, $data, $type, $options): AssessmentQuestion {
            $question = AssessmentQuestion::create([
                'assessment_id' => $assessment->id,
                'type' => $type->value,
                'prompt' => $this->sanitizer->sanitize((string) $data['prompt']),
                'config' => $data['config'] ?? null,
                'explanation' => $this->sanitizeNullable($data['explanation'] ?? null),
                'hint' => $this->sanitizeNullable($data['hint'] ?? null),
                'points' => $data['points'] ?? 1,
                'negative_points' => $data['negative_points'] ?? 0,
                'difficulty' => $data['difficulty'] ?? null,
                // Append to the end; explicit ordering is a separate, deliberate action.
                'position' => (int) AssessmentQuestion::where('assessment_id', $assessment->id)->max('position') + 1,
            ]);

            $this->syncOptions($question, $options);

            return $question->load('options');
        });
    }

    /** @param  array<string, mixed>  $data */
    public function applyTo(AssessmentQuestion $question, array $data): AssessmentQuestion
    {
        // The type may change (e.g. single_choice → multiple_choice); the option set is then
        // re-validated against the NEW type, so a change that would orphan the answer key fails.
        $type = isset($data['type']) ? QuestionType::from((string) $data['type']) : $question->type;

        $options = array_key_exists('options', $data)
            ? $this->normalizeOptions($data['options'])
            : $question->options->map(fn ($o) => [
                'label' => $o->label,
                'value' => $o->value,
                'is_correct' => $o->is_correct,
                'group_index' => $o->group_index,
                'feedback' => $o->feedback,
            ])->all();

        $this->shapeGuard->assertValid($type, $options);

        return DB::transaction(function () use ($question, $data, $type, $options): AssessmentQuestion {
            $attributes = ['type' => $type->value];

            if (array_key_exists('prompt', $data)) {
                $attributes['prompt'] = $this->sanitizer->sanitize((string) $data['prompt']);
            }
            foreach (['config', 'points', 'negative_points', 'difficulty'] as $key) {
                if (array_key_exists($key, $data)) {
                    $attributes[$key] = $data[$key];
                }
            }
            foreach (['explanation', 'hint'] as $key) {
                if (array_key_exists($key, $data)) {
                    $attributes[$key] = $this->sanitizeNullable($data[$key]);
                }
            }

            $question->fill($attributes)->save();

            if (array_key_exists('options', $data)) {
                $this->syncOptions($question, $options);
            }

            return $question->refresh()->load('options');
        });
    }

    /**
     * Replace the option set wholesale. Rows are deleted and recreated rather than diffed: option
     * ids are referenced by saved answers, and silently reusing an id whose meaning changed would
     * corrupt the grading of attempts already in progress. Fresh ids make that impossible.
     *
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(AssessmentQuestion $question, array $options): void
    {
        $question->options()->delete();

        foreach (array_values($options) as $position => $option) {
            $question->options()->create([
                'label' => $option['label'] ?? null,
                'value' => $option['value'] ?? null,
                'is_correct' => (bool) ($option['is_correct'] ?? false),
                'group_index' => (int) ($option['group_index'] ?? 0),
                'feedback' => $option['feedback'] ?? null,
                'position' => $position,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        // array_filter preserves keys, so the result is not yet a list — array_values makes it one.
        /** @var array<int, array<string, mixed>> $filtered */
        $filtered = array_filter($options, 'is_array');

        return array_values($filtered);
    }

    private function sanitizeNullable(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $this->sanitizer->sanitize($value) : null;
    }
}
