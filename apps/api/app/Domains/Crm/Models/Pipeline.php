<?php

namespace App\Domains\Crm\Models;

use App\Domains\Crm\Enums\PipelineType;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use HasPublicId;

    protected $table = 'crm_pipelines';

    protected $fillable = ['name', 'type', 'is_default'];

    protected function casts(): array
    {
        return ['type' => PipelineType::class, 'is_default' => 'boolean'];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('position');
    }
}
