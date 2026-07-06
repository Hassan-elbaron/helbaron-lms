<?php

namespace App\Domains\Certification\Http\Controllers\Api\V1\Admin;

use App\Domains\Certification\Actions\ReissueCertificateAction;
use App\Domains\Certification\Actions\RevokeCertificateAction;
use App\Domains\Certification\Http\Resources\CertificateResource;
use App\Domains\Certification\Models\Certificate;
use App\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class CertificateAdminController extends Controller
{
    public function revoke(Certificate $certificate, RevokeCertificateAction $action): JsonResponse
    {
        Gate::authorize('manage', $certificate);

        return ApiResponse::updated(new CertificateResource($action->execute($certificate)->load('course')), 'Certificate revoked.');
    }

    public function reissue(Certificate $certificate, ReissueCertificateAction $action): JsonResponse
    {
        Gate::authorize('manage', $certificate);

        return ApiResponse::updated(new CertificateResource($action->execute($certificate)->load('course')), 'Certificate reissued.');
    }
}
