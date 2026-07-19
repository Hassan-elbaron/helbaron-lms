<?php

namespace App\Domains\Authoring\Models;

use App\Domains\Authoring\Database\Factories\SectionFactory;
use App\Domains\Authoring\Enums\PublishState;
use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A curriculum section belonging to a Catalog course. Holds ordered lessons.
 *
 * Only the column read across a context boundary is annotated here; the rest predate the
 * annotation convention and are covered by the PHPStan baseline.
 *
 * @property int $course_id
 */
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $table = 'course_sections';

    protected $fillable = ['course_id', 'title', 'summary', 'position', 'publish_state'];

    protected function casts(): array
    {
        return [
            'publish_state' => PublishState::class,
            'position' => 'integer',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publish_state', PublishState::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->publish_state === PublishState::Published;
    }

    protected static function newFactory(): SectionFactory
    {
        return SectionFactory::new();
    }
}
