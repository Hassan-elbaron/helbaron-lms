<?php

namespace App\Domains\Certification\Models;

use App\Domains\Certification\Database\Factories\BadgeFactory;
use App\Shared\Traits\HasPublicId;
use App\Shared\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    /** @use HasFactory<BadgeFactory> */
    use HasFactory;

    use HasPublicId;
    use HasSlug;

    protected $fillable = ['name', 'slug', 'description', 'icon_path', 'criteria', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function newFactory(): BadgeFactory
    {
        return BadgeFactory::new();
    }
}
