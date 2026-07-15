<?php

namespace App\Domains\Live\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read model over the `session_trainers` pivot. Carries the trainer's user_id (and role/position)
 * WITHOUT a relation to the Identity User model — trainer display is resolved from these ids
 * through the IdentityContracts UserLookupPort. Pivot writes go through LiveSession::syncTrainers().
 */
class SessionTrainer extends Model
{
    protected $table = 'session_trainers';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];
}
