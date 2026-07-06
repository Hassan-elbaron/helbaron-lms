<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\DigestFrequency;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationSetting extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'locale', 'digest_frequency', 'timezone'];

    protected function casts(): array
    {
        return ['digest_frequency' => DigestFrequency::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
