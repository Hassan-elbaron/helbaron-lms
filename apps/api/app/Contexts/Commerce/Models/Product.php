<?php

namespace App\Contexts\Commerce\Models;

use App\Contexts\Commerce\Database\Factories\ProductFactory;
use App\Contexts\Commerce\Enums\ProductStatus;
use App\Contexts\Commerce\Enums\ProductType;
use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Traits\HasPublicId;
use App\Platform\Shared\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A purchasable product. Grants one or more courses on purchase (single course or bundle).
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasPublicId;
    use HasSlug;
    use SoftDeletes;

    protected $fillable = ['type', 'title', 'slug', 'description', 'status'];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'status' => ProductStatus::class,
        ];
    }

    public function slugSource(): string
    {
        return 'title';
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'product_courses');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
