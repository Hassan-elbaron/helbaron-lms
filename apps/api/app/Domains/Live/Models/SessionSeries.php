<?php

namespace App\Domains\Live\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionSeries extends Model
{
    use HasPublicId;

    protected $table = 'session_series';

    protected $fillable = ['live_course_id', 'title', 'recurrence', 'timezone'];

    public function liveCourse(): BelongsTo
    {
        return $this->belongsTo(LiveCourse::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(LiveSession::class, 'series_id');
    }
}
