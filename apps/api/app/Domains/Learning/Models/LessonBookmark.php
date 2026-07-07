<?php

namespace App\Domains\Learning\Models;

use App\Domains\Authoring\Models\Lesson;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonBookmark extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'lesson_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
