<?php

namespace App\Domains\Catalog\Events;

use App\Domains\Catalog\Models\Category;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoryCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Category $category) {}
}
