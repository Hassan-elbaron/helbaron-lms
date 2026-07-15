<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Database\Factories\CourseAnnouncementFactory;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An instructor-authored announcement attached to a Catalog course. Persistence only — the
 * fan-out to enrolled learners is performed by the Notifications context via the controller.
 * `author_id` holds the authoring user id (no relation to the Identity User model here).
 */
class CourseAnnouncement extends Model
{
    /** @use HasFactory<CourseAnnouncementFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = ['course_id', 'author_id', 'title', 'body', 'published_at'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    protected static function newFactory(): CourseAnnouncementFactory
    {
        return CourseAnnouncementFactory::new();
    }
}
