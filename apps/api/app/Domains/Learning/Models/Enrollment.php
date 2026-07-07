<?php

namespace App\Domains\Learning\Models;

use App\Domains\Catalog\Models\Course;
use App\Domains\Identity\Models\User;
use App\Domains\Learning\Database\Factories\EnrollmentFactory;
use App\Domains\Learning\Enums\EnrollmentSource;
use App\Domains\Learning\Enums\EnrollmentStatus;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A learner's relationship with a course. Owns status and completion percentage. Entitlement
 * is granted by Learning actions (Commerce will call GrantEnrollmentAction later).
 */
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'course_id', 'status', 'source', 'progress_percentage', 'enrolled_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'source' => EnrollmentSource::class,
            'progress_percentage' => 'integer',
            'enrolled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', EnrollmentStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === EnrollmentStatus::Active;
    }

    protected static function newFactory(): EnrollmentFactory
    {
        return EnrollmentFactory::new();
    }
}
