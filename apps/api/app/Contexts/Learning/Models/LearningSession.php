<?php

namespace App\Contexts\Learning\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;

/**
 * "Where did I leave off" per (user, course). Updated as the learner records progress. Holds the
 * course_id / last_lesson_id foreign keys as scalar columns; cross-context course/lesson relations
 * were removed (Curriculum coupling) — resolve via CurriculumReadPort where display data is needed.
 */
class LearningSession extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'course_id', 'last_lesson_id', 'last_activity_at'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }
}
