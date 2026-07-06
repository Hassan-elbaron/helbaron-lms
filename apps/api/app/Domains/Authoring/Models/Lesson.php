<?php

namespace App\Domains\Authoring\Models;

use App\Domains\Authoring\Database\Factories\LessonFactory;
use App\Domains\Authoring\Enums\LessonType;
use App\Domains\Authoring\Enums\PublishState;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A lesson within a section. Carries type + content metadata and (for media types) a single
 * LessonMedia metadata row. No playback, progress, or enrollment logic lives here.
 */
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = ['section_id', 'title', 'type', 'content', 'position', 'publish_state', 'is_preview'];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'publish_state' => PublishState::class,
            'content' => 'array',
            'position' => 'integer',
            'is_preview' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function media(): HasOne
    {
        return $this->hasOne(LessonMedia::class);
    }

    /** Lessons that must be completed before this one. */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'lesson_prerequisites', 'lesson_id', 'prerequisite_lesson_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publish_state', PublishState::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->publish_state === PublishState::Published;
    }

    protected static function newFactory(): LessonFactory
    {
        return LessonFactory::new();
    }
}
