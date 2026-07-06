<?php

namespace App\Domains\Certification\Http\Controllers\Api\V1;

use App\Domains\Certification\Http\Resources\VerificationResource;
use App\Domains\Certification\Services\CertificateVerificationService;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * PUBLIC certificate verification — no authentication.
 */
class VerificationController extends Controller
{
    public function show(string $code, CertificateVerificationService $service): JsonResponse
    {
        $result = $service->verify($code);

        if ($result === null) {
            throw new NotFoundHttpException('Certificate not found.');
        }

        return ApiResponse::success(new VerificationResource($result));
    }
}
