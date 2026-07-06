<?php

namespace App\Domains\Catalog\Actions\Category;

use App\Domains\Catalog\Models\Category;
use App\Shared\Actions\BaseAction;

class ReorderCategoriesAction extends BaseAction
{
    /**
     * @param  array<int, string>  $orderedPublicIds
     */
    public function execute(array $orderedPublicIds): void
    {
        $this->transaction(function () use ($orderedPublicIds): void {
            foreach ($orderedPublicIds as $position => $publicId) {
                Category::where('public_id', $publicId)->update(['position' => $position]);
            }
        });
    }
}
