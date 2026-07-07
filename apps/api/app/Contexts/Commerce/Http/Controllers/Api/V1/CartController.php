<?php

namespace App\Contexts\Commerce\Http\Controllers\Api\V1;

use App\Contexts\Commerce\Actions\Cart\AddToCartAction;
use App\Contexts\Commerce\Actions\Cart\ApplyCouponAction;
use App\Contexts\Commerce\Actions\Cart\ClearCartAction;
use App\Contexts\Commerce\Http\Requests\AddToCartRequest;
use App\Contexts\Commerce\Http\Resources\CartResource;
use App\Contexts\Commerce\Models\Product;
use App\Contexts\Commerce\Services\CartService;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartController extends Controller
{
    public function show(Request $request, CartService $carts): JsonResponse
    {
        $cart = $carts->current($request->user())->load(['items.product', 'coupon']);

        return ApiResponse::success(new CartResource(['cart' => $cart, 'totals' => $carts->totals($cart)]));
    }

    public function store(AddToCartRequest $request, AddToCartAction $add, ApplyCouponAction $applyCoupon, CartService $carts): JsonResponse
    {
        $data = $request->validated();

        $product = Product::where('public_id', $data['product'])->first();
        if ($product === null) {
            throw new NotFoundHttpException('Product not found.');
        }

        $add->execute($request->user(), $product);

        if (! empty($data['coupon_code'])) {
            $applyCoupon->execute($request->user(), $data['coupon_code']);
        }

        $cart = $carts->current($request->user())->load(['items.product', 'coupon']);

        return ApiResponse::success(new CartResource(['cart' => $cart, 'totals' => $carts->totals($cart)]), 'Cart updated.');
    }

    public function destroy(Request $request, ClearCartAction $clear): JsonResponse
    {
        $clear->execute($request->user());

        return ApiResponse::deleted('Cart cleared.');
    }
}
