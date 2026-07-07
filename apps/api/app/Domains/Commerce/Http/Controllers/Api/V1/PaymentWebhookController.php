<?php

namespace App\Domains\Commerce\Http\Controllers\Api\V1;

use App\Domains\Commerce\Actions\Payment\ProcessWebhookAction;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, ProcessWebhookAction $action): JsonResponse
    {
        // Signature header name varies by provider; the gateway verifies it.
        $signature = $request->header('X-Signature') ?? $request->header('Stripe-Signature');

        $action->execute($request->getContent(), $signature);

        return ApiResponse::success(null, 'ok');
    }
}
