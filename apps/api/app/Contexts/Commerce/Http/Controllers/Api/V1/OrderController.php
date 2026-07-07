<?php

namespace App\Contexts\Commerce\Http\Controllers\Api\V1;

use App\Contexts\Commerce\Http\Resources\OrderResource;
use App\Contexts\Commerce\Models\Order;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items', 'invoice'])
            ->latest('id')
            ->paginate((int) request('per_page', 15))->withQueryString();

        return ApiResponse::paginated($orders, OrderResource::class);
    }
}
