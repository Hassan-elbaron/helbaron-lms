<?php

namespace App\Platform\Notifications\Models;

use App\Platform\Notifications\Enums\DigestFrequency;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'locale', 'digest_frequency', 'timezone'];

    protected function casts(): array
    {
        return ['digest_frequency' => DigestFrequency::class];
    }
}
