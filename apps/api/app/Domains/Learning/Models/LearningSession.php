<?php

namespace App\Domains\Learning\Models;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Catalog\Models\Course;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Where did I leave off" per (user, course). Updated as the learner records progress.
 */
class LearningSession extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'course_id', 'last_lesson_id', 'last_activity_at'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lastLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'last_lesson_id');
    }
}
