<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Database\Factories\CourseTagFactory;
use App\Shared\Traits\HasPublicId;
use App\Shared\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseTag extends Model
{
    /** @use HasFactory<CourseTagFactory> */
    use HasFactory;

    use HasPublicId;
    use HasSlug;

    protected $fillable = ['name', 'slug'];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_tag', 'tag_id', 'course_id');
    }

    protected static function newFactory(): CourseTagFactory
    {
        return CourseTagFactory::new();
    }
}
