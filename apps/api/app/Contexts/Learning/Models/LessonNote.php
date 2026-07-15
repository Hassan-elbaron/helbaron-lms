<?php

namespace App\Contexts\Learning\Models;

use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class LessonNote extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'lesson_id', 'body'];
}
