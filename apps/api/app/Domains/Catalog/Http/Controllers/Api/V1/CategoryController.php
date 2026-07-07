<?php

namespace App\Domains\Catalog\Http\Controllers\Api\V1;

use App\Domains\Catalog\Http\Resources\CategoryResource;
use App\Domains\Catalog\Models\Category;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CategoryController extends Controller
{
    /** Returns the active category tree (roots with nested children). */
    public function index(): JsonResponse
    {
        $roots = Category::query()
            ->roots()
            ->active()
            ->with('children')
            ->orderBy('position')
            ->get();

        return ApiResponse::success(CategoryResource::collection($roots));
    }
}
