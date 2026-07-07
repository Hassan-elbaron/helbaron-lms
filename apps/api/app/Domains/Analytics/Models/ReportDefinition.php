<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Database\Factories\ReportDefinitionFactory;
use App\Domains\Analytics\Enums\ReportType;
use App\Domains\Analytics\Enums\ReportVisibility;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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
