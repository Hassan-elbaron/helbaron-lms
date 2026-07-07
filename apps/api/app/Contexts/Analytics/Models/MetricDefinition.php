<?php

namespace App\Contexts\Analytics\Models;

use App\Contexts\Analytics\Enums\MetricCategory;
use App\Contexts\Analytics\Enums\MetricUnit;
use App\Platform\Shared\Traits\HasPublicId;
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
