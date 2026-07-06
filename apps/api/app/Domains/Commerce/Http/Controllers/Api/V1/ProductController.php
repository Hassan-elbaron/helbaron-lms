<?php

namespace App\Domains\Commerce\Http\Controllers\Api\V1;

use App\Domains\Commerce\Http\Resources\ProductResource;
use App\Domains\Commerce\Models\Product;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()->active()->with('prices')->orderByDesc('id')
            ->paginate((int) request('per_page', 15))->withQueryString();

        return ApiResponse::paginated($products, ProductResource::class);
    }
}
