<?php

namespace App\Domains\Certification\Http\Controllers\Api\V1;

use App\Domains\Certification\Http\Resources\CertificateListItemResource;
use App\Domains\Certification\Models\Certificate;
use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MyCertificatesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $certificates = Certificate::query()
            ->where('user_id', $request->user()->id)
            ->with('course')
            ->latest('id')
            ->get();

        return ApiResponse::success(CertificateListItemResource::collection($certificates));
    }
}
