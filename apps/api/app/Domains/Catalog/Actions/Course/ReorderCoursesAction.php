<?php

namespace App\Domains\Catalog\Actions\Course;

use App\Domains\Catalog\Models\Course;
use App\Platform\Shared\Actions\BaseAction;

class ReorderCoursesAction extends BaseAction
{
    /**
     * @param  array<int, string>  $orderedPublicIds
     */
    public function execute(array $orderedPublicIds): void
    {
        $this->transaction(function () use ($orderedPublicIds): void {
            foreach ($orderedPublicIds as $position => $publicId) {
                Course::where('public_id', $publicId)->update(['position' => $position]);
            }
        });
    }
}
