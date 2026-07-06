<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Database\Factories\CategoryFactory;
use App\Shared\Traits\HasPublicId;
use App\Shared\Traits\HasSeo;
use App\Shared\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Nested category. A category may have a parent and many children (self-referential tree).
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasPublicId;
    use HasSeo;
    use HasSlug;
    use SoftDeletes;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'position', 'is_active', 'seo',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position' => 'integer',
            'seo' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_category');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
