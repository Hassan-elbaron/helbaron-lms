<?php

namespace App\Domains\Certification\Http\Controllers\Api\V1;

use App\Domains\Certification\Actions\EnsureCertificatePdfAction;
use App\Domains\Certification\Models\Certificate;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Streams the certificate PDF. Reached only via a signed URL (no auth guard) — the signature
 * authorizes access and the storage path is never revealed.
 */
class CertificateFileController extends Controller
{
    public function __invoke(string $certificate, EnsureCertificatePdfAction $ensure): Response
    {
        $model = Certificate::where('public_id', $certificate)->first();

        if ($model === null || ! $model->isValid()) {
            throw new NotFoundHttpException('Certificate not available.');
        }

        $ensure->execute($model);
        $disk = Storage::disk((string) config('certification.pdf.disk', 'local'));

        return response($disk->get($model->pdf_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$model->number.'.pdf"',
        ]);
    }
}
