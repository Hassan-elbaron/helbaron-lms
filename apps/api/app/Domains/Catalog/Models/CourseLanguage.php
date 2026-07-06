<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Catalog\Database\Factories\CourseLanguageFactory;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLanguage extends Model
{
    /** @use HasFactory<CourseLanguageFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['code', 'name', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    protected static function newFactory(): CourseLanguageFactory
    {
        return CourseLanguageFactory::new();
    }
}
