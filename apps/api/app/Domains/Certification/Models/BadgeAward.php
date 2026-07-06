<?php

namespace App\Domains\Certification\Models;

use App\Domains\Certification\Enums\BadgeSource;
use App\Domains\Identity\Models\User;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BadgeAward extends Model
{
    use HasPublicId;

    protected $fillable = ['badge_id', 'user_id', 'source', 'awarded_at'];

    protected function casts(): array
    {
        return ['source' => BadgeSource::class, 'awarded_at' => 'datetime'];
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(Badge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
