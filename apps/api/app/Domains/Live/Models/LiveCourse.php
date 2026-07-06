<?php

namespace App\Domains\Live\Models;

use App\Domains\Catalog\Models\Course;
use App\Domains\Live\Database\Factories\LiveCourseFactory;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveCourse extends Model
{
    /** @use HasFactory<LiveCourseFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = ['course_id', 'title', 'description', 'timezone', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(LiveSession::class);
    }

    protected static function newFactory(): LiveCourseFactory
    {
        return LiveCourseFactory::new();
    }
}
