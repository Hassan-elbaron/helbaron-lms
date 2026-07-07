<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Database\Factories\CourseFactory;
use App\Domains\Catalog\Enums\CourseStatus;
use App\Domains\Identity\Models\User;
use App\Platform\Shared\Enums\Visibility;
use App\Platform\Shared\Traits\HasPublicId;
use App\Platform\Shared\Traits\HasSeo;
use App\Platform\Shared\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Catalog course aggregate. Owns metadata, taxonomy links, visibility, featuring and publish
 * lifecycle. Curriculum (sections/lessons) belongs to Authoring — not here.
 */
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    use HasPublicId;
    use HasSeo;
    use HasSlug;
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'subtitle', 'description', 'level_id', 'language_id',
        'status', 'visibility', 'is_featured', 'thumbnail_path', 'position', 'published_at', 'seo',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'visibility' => Visibility::class,
            'is_featured' => 'boolean',
            'position' => 'integer',
            'published_at' => 'datetime',
            'seo' => 'array',
        ];
    }

    /** Slug source is the title (overrides HasSlug default 'name'). */
    public function slugSource(): string
    {
        return 'title';
    }

    // ----- Relations -----

    public function level(): BelongsTo
    {
        return $this->belongsTo(CourseLevel::class, 'level_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(CourseLanguage::class, 'language_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'course_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CourseTag::class, 'course_tag', 'course_id', 'tag_id');
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_trainer')->withPivot('position');
    }

    // ----- Scopes -----

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published->value);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visibility', Visibility::Public->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }
}
