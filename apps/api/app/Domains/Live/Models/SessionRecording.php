<?php

namespace App\Domains\Live\Models;

use App\Domains\Live\Enums\RecordingStatus;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionRecording extends Model
{
    use HasPublicId;

    protected $fillable = ['session_id', 'provider', 'external_id', 'url', 'duration_seconds', 'status', 'recorded_at'];

    protected function casts(): array
    {
        return [
            'status' => RecordingStatus::class,
            'duration_seconds' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class, 'session_id');
    }
}
