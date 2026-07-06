<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\MetricCategory;
use App\Domains\Analytics\Enums\MetricUnit;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class MetricDefinition extends Model
{
    use HasPublicId;

    protected $fillable = ['key', 'name', 'category', 'unit', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['category' => MetricCategory::class, 'unit' => MetricUnit::class, 'is_active' => 'boolean'];
    }
}
