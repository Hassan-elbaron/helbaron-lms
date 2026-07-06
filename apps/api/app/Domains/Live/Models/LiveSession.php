<?php

namespace App\Domains\Live\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Live\Database\Factories\LiveSessionFactory;
use App\Domains\Live\Enums\LiveSessionStatus;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveSession extends Model
{
    /** @use HasFactory<LiveSessionFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'live_course_id', 'series_id', 'title', 'description', 'status', 'timezone',
        'starts_at', 'ends_at', 'capacity', 'waiting_room', 'meeting_provider', 'meeting_external_id', 'join_url',
    ];

    protected $hidden = ['join_url']; // raw meeting URL is never serialized; use /join instead

    protected function casts(): array
    {
        return [
            'status' => LiveSessionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'waiting_room' => 'boolean',
        ];
    }

    public function liveCourse(): BelongsTo
    {
        return $this->belongsTo(LiveCourse::class);
    }

    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'session_trainers', 'session_id', 'user_id')->withPivot('role', 'position');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(SessionRegistration::class, 'session_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class, 'session_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(SessionRecording::class, 'session_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(SessionReminder::class, 'session_id');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', LiveSessionStatus::Scheduled->value)->where('starts_at', '>=', now());
    }

    public function isCancelled(): bool
    {
        return $this->status === LiveSessionStatus::Cancelled;
    }

    public function registeredCount(): int
    {
        return $this->registrations()->where('status', 'registered')->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->registeredCount() >= $this->capacity;
    }

    protected static function newFactory(): LiveSessionFactory
    {
        return LiveSessionFactory::new();
    }
}
