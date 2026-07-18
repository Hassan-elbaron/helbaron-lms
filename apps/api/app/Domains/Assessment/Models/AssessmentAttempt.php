<?php

namespace App\Domains\Assessment\Models;

use App\Domains\Assessment\Database\Factories\AssessmentAttemptFactory;
use App\Domains\Assessment\Enums\AttemptStatus;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One learner sitting. `question_order` is the frozen serve order captured at start — grading and
 * re-rendering both read from it, so a shuffled or subset attempt is reproducible and auditable.
 *
 * Score columns are `decimal:2`, which Laravel casts to a formatted string on read.
 *
 * @property int $id
 * @property string $public_id
 * @property int $assessment_id
 * @property int $user_id
 * @property int|null $lesson_id where the attempt was taken from; null for a direct sitting
 * @property int $attempt_number
 * @property int $assessment_version pinned so later edits never change what was asked
 * @property AttemptStatus $status
 * @property Carbon $started_at
 * @property Carbon|null $expires_at set only when the assessment is timed
 * @property Carbon|null $submitted_at
 * @property Carbon|null $graded_at
 * @property string|null $score decimal:2, points awarded
 * @property string|null $max_score decimal:2, points available in THIS attempt
 * @property string|null $percentage decimal:2
 * @property bool|null $passed null = not graded, or the assessment has no pass mark
 * @property array<int, string>|null $question_order question public_ids in serve order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Assessment|null $assessment
 * @property-read Collection<int, AssessmentAnswer> $answers
 * @property-read int|null $answers_count
 */
class AssessmentAttempt extends Model
{
    /** @use HasFactory<AssessmentAttemptFactory> */
    use HasFactory;

    use HasPublicId;

    /** @var list<string> */
    protected $fillable = [
        'assessment_id', 'user_id', 'lesson_id', 'attempt_number', 'assessment_version', 'status',
        'started_at', 'expires_at', 'submitted_at', 'graded_at',
        'score', 'max_score', 'percentage', 'passed', 'question_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'attempt_number' => 'integer',
            'assessment_version' => 'integer',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'percentage' => 'decimal:2',
            'passed' => 'boolean',
            'question_order' => 'array',
        ];
    }

    /** @return BelongsTo<Assessment, $this> */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** @return HasMany<AssessmentAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class, 'attempt_id');
    }

    /** True once a timed attempt's window has closed. Checked on every write to the attempt. */
    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Answers may only be written while the attempt is open AND inside its time window. */
    public function acceptsAnswers(): bool
    {
        return $this->status->isOpen() && ! $this->hasExpired();
    }

    /**
     * Frozen question public_ids, in serve order.
     *
     * The values are re-checked against `is_string` rather than trusted from the cast: `question_order`
     * is a JSON column, so its contents are whatever was last written to the database, not whatever
     * the property annotation claims.
     *
     * @return list<string>
     */
    public function questionOrder(): array
    {
        $order = $this->question_order;

        if (! is_array($order)) {
            return [];
        }

        return array_values(array_filter($order, 'is_string'));
    }

    protected static function newFactory(): AssessmentAttemptFactory
    {
        return AssessmentAttemptFactory::new();
    }
}
