<?php

namespace App\Platform\Notifications\Models;

use App\Platform\Identity\Models\User;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'category', 'channel', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
