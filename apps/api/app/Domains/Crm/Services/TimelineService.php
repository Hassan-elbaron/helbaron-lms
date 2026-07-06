<?php

namespace App\Domains\Crm\Services;

use App\Shared\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Reads a subject's combined timeline (activities, ordered most-recent-first).
 */
class TimelineService extends BaseService
{
    public function forSubject(Model $subject, int $limit = 50): Collection
    {
        return $subject->activities()->with('user')->limit($limit)->get();
    }
}
