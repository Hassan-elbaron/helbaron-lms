<?php

namespace App\Domains\Live\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Live\Enums\AttendanceSource;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAttendance extends Model
{
    use HasPublicId;

    protected $fillable = ['session_id', 'user_id', 'source', 'joined_at', 'left_at', 'duration_seconds'];

    protected function casts(): array
    {
        return [
            'source' => AttendanceSource::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
