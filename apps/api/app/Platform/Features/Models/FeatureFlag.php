<?php

namespace App\Platform\Features\Models;

use App\Platform\Features\Database\Factories\FeatureFlagFactory;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A single feature flag row. Flags gate PRESENTATION / rollout only (never core domain routes in
 * this pass). Evaluation lives in FeatureFlagService, which treats a MISSING flag as enabled and
 * an ENABLED flag as the base state further constrained by environment / roles / rollout / window.
 *
 * @property int $id
 * @property string $public_id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property bool $is_enabled
 * @property string|null $environment
 * @property array<int, string>|null $roles
 * @property int|null $rollout_percentage
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $owner
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FeatureFlag extends Model
{
    /** @use HasFactory<FeatureFlagFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = [
        'key', 'name', 'description', 'is_enabled', 'environment',
        'roles', 'rollout_percentage', 'starts_at', 'ends_at', 'owner',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'roles' => 'array',
            'rollout_percentage' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function newFactory(): FeatureFlagFactory
    {
        return FeatureFlagFactory::new();
    }
}
