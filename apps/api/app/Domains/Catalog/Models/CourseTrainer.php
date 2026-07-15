<?php

namespace App\Domains\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read model over the `course_trainer` pivot. Carries the trainer's user_id (and position) WITHOUT
 * a relation to the Identity User model — trainer display is resolved from these ids through the
 * IdentityContracts UserLookupPort. Pivot writes go through Course::syncTrainers().
 */
class CourseTrainer extends Model
{
    protected $table = 'course_trainer';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
