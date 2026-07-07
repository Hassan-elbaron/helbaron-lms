<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Database\Factories\CourseLevelFactory;
use App\Platform\Shared\Traits\HasPublicId;
use App\Platform\Shared\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLevel extends Model
{
    /** @use HasFactory<CourseLevelFactory> */
    use HasFactory;

    use HasPublicId;
    use HasSlug;

    protected $fillable = ['name', 'slug', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    protected static function newFactory(): CourseLevelFactory
    {
        return CourseLevelFactory::new();
    }
}
