<?php

namespace App\Domains\Crm\Models;

use App\Platform\Shared\Tenancy\Concerns\BelongsToTenant;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatPool extends Model
{
    use BelongsToTenant;
    use HasPublicId;

    protected $fillable = ['organization_id', 'name', 'total_seats', 'used_seats'];

    protected function casts(): array
    {
        return ['total_seats' => 'integer', 'used_seats' => 'integer'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SeatAssignment::class);
    }

    public function available()