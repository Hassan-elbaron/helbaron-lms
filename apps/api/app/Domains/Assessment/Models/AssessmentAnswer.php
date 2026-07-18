<?php

namespace App\Domains\Assessment\Models;

use App\Domains\Assessment\Database\Factories\AssessmentAnswerFactory;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A learner's response to one question in one attempt.
 *
 * `response` is a json envelope whose shape is decided by the question type, which is why no
 * question type ever needs a schema change here:
 *   single_choice / true_false → {"option_ids": ["<uuid>"]}
 *   multiple_choice           → {"option_ids": ["<uuid>", "<uuid>"]}
 *   short_answer              → {"text": "photosynthesis"}
 *   fill_in_blank             → {"blanks": {"0": "carbon", "1": "dioxide"}}
 * It is therefore genuinely heterogeneous rather than under-specified; the accessors below do the
 * narrowing, and they validate rather than trust, because the payload is attacker-controlled.
 *
 * `is_correct === null` means NOT YET GRADED — the state a manually-graded answer waits in.
 *
 * @property int $id
 * @property string $public_id
 * @property int $attempt_id
 * @property int $question_id
 * @property array<string, mixed>|null $response null = seen but unanswered
 * @property bool|null $is_correct null = pending grading
 * @property string|null $points_awarded decimal:2
 * @property Carbon|null $graded_at
 * @property int|null $grader_id set by a human grader on manually-graded types
 * @property string|null $feedback
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AssessmentAttempt|null $attempt
 * @property-read AssessmentQuestion|null $question
 */
class AssessmentAnswer extends Model
{
    /** @use HasFactory<AssessmentAnswerFactory> */
    use HasFactory;

    use HasPublicId;

    /** @var list<string> */
    protected $fillable = [
        'attempt_id', 'question_id', 'response',
        'is_correct', 'points_awarded', 'graded_at', 'grader_id', 'feedback',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'response' => 'array',
            'is_correct' => 'boolean',
            'points_awarded' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AssessmentAttempt, $this> */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(AssessmentAttempt::class, 'attempt_id');
    }

    /** @return BelongsTo<AssessmentQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id');
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }

    /**
     * Selected option public_ids, for choice-based types. Returns [] for any other shape, so a
     * malformed or hostile payload can never reach the grader as something else.
     *
     * @return list<string>
     */
    public function selectedOptionIds(): array
    {
        $response = $this->response;
        $ids = is_array($response) ? ($response['option_ids'] ?? null) : null;

        return is_array($ids) ? array_values(array_filter($ids, 'is_string')) : [];
    }

    /** Free-text response for text-matched types. */
    public function text(): string
    {
        $response = $this->response;
        $text = is_array($response) ? ($response['text'] ?? null) : null;

        return is_string($text) ? $text : '';
    }

    /**
     * Per-blank responses for fill_in_blank, keyed by blank index.
     *
     * Non-numeric keys are DROPPED, not coerced. PHP casts a non-numeric string to 0, so accepting
     * `{"blanks": {"abc": "guess"}}` would silently answer blank 0 — letting a malformed or hostile
     * payload fill a blank the learner never addressed. Only genuine blank indices are kept.
     *
     * Keys are ints because PHP normalises numeric-string array keys to integers anyway; declaring
     * them as strings would be a type the runtime never actually produces.
     *
     * @return array<int, string>
     */
    public function blanks(): array
    {
        $response = $this->response;
        $blanks = is_array($response) ? ($response['blanks'] ?? null) : null;

        if (! is_array($blanks)) {
            return [];
        }

        $clean = [];
        foreach ($blanks as $index => $value) {
            if (is_string($value) && (is_int($index) || ctype_digit($index))) {
                $clean[(int) $index] = $value;
            }
        }

        return $clean;
    }

    protected static function newFactory(): AssessmentAnswerFactory
    {
        return AssessmentAnswerFactory::new();
    }
}
