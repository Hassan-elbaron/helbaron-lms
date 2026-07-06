<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Database\Factories\MetricSnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The analytics read model. Analytics reads this exclusively — never operational tables.
 */
class MetricSnapshot extends Model
{
    /** @use HasFactory<MetricSnapshotFactory> */
    use HasFactory;

    protected $fillable = ['metric_key', 'granularity', 'period', 'dimension_key', 'dimension_value', 'value'];

    protected function casts(): array
    {
        return ['period' => 'date', 'value' => 'integer'];
    }

    protected static function newFactory(): MetricSnapshotFactory
    {
        return MetricSnapshotFactory::new();
    }
}
