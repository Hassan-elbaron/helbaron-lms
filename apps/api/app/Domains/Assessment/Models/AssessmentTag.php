<?php

namespace App\Domains\Assessment\Models;

use App\Domains\Assessment\Database\Factories\AssessmentTagFactory;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;

/**
 * A tag or a learning objective — distinguished by `kind`, sharing one table because their storage
 * is identical. Attached polymorphically so the same label can later hang off an assessment, a
 * course or a question bank without another pivot.
 *
 * @property int $id
 * @property string $public_id
 * @property string $kind self::KIND_TAG | self::KIND_OBJECTIVE
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, AssessmentQuestion> $questions
 * @property-read Collection<int, Assessment> $assessments
 */
class AssessmentTag extends Model
{
    public const KIND_TAG = 'tag';

    public const KIND_OBJECTIVE = 'objective';

    /** @use HasFactory<AssessmentTagFactory> */
    use HasFactory;

    use HasPublicId;

    /** @var list<string> */
    protected $fillable = ['kind', 'name', 'slug'];

    /** @return MorphToMany<AssessmentQuestion, $this> */
    public function questions(): MorphToMany
    {
        return $this->morphedByMany(
            AssessmentQuestion::class, 'taggable', 'assessment_taggables', 'tag_id', 'taggable_id',
        );
    }

    /** @return MorphToMany<Assessment, $this> */
    public function assessments(): MorphToMany
    {
        return $this->morphedByMany(
            Assessment::class, 'taggable', 'assessment_taggables', 'tag_id', 'taggable_id',
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeObjectives(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_OBJECTIVE);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeTags(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_TAG);
    }

    protected static function newFactory(): AssessmentTagFactory
    {
        return AssessmentTagFactory::new();
    }
}
