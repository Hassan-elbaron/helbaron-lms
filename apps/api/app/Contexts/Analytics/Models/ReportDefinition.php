<?php

namespace App\Contexts\Analytics\Models;

use App\Contexts\Analytics\Database\Factories\ReportDefinitionFactory;
use App\Contexts\Analytics\Enums\ReportType;
use App\Contexts\Analytics\Enums\ReportVisibility;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A saved report definition. `metric_keys` is annotated because authorization now reads it to
 * decide whether the report carries money — an undeclared json-cast property degrades to mixed,
 * which is not a thing to rely on in a security check.
 *
 * Typed `mixed` rather than `string` on purpose: this is a JSON column, so its contents are
 * whatever was last written to the database — not whatever an annotation claims. Readers narrow
 * each element themselves.
 *
 * @property array<int, mixed>|null $metric_keys
 */
class ReportDefinition extends Model
{
    /** @use HasFactory<ReportDefinitionFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['name', 'type', 'metric_keys', 'filters', 'visibility', 'owner_id'];

    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'visibility' => ReportVisibility::class,
            'metric_keys' => 'array',
            'filters' => 'array',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class);
    }

    protected static function newFactory(): ReportDefinitionFactory
    {
        return ReportDefinitionFactory::new();
    }
}
