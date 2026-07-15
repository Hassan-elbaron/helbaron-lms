<?php

namespace App\Domains\Certification\Actions;

use App\Domains\Catalog\Models\Course;
use App\Domains\Certification\Enums\CertificateStatus;
use App\Domains\Certification\Events\CertificateIssued;
use App\Domains\Certification\Models\Certificate;
use App\Domains\Certification\Models\CertificateSetting;
use App\Domains\Certification\Models\CertificateTemplate;
use App\Domains\Certification\Services\CertificateNumberService;
use App\Domains\Certification\Services\SignatureService;
use App\Domains\Certification\Services\VerificationCodeService;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Issues a certificate for a completed course. Idempotent per (user, course): a repeated
 * CourseCompleted never mints a duplicate. PDF is rendered lazily (EnsureCertificatePdfAction).
 */
class GenerateCertificateAction extends BaseAction
{
    public function __construct(
        private readonly CertificateNumberService $numbers,
        private readonly VerificationCodeService $codes,
        private readonly SignatureService $signatures,
    ) {}

    public function executeByUserId(int $userId, Course $course, ?int $enrollmentId = null): Certificate
    {
        [$certificate, $created] = $this->transaction(function () use ($userId, $course, $enrollmentId): array {
            $existing = Certificate::where('user_id', $userId)
                ->where('course_id', $course->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return [$existing, false];
            }

            $settings = CertificateSetting::current();
            $template = CertificateTemplate::where('is_active', true)->orderByDesc('version')->first();

            $certificate = Certificate::create([
                'user_id' => $userId,
                'course_id' => $course->id,
                'enrollment_id' => $enrollmentId,
                'template_id' => $template?->id ?? $settings->default_template_id,
                'number' => $this->numbers->next(),
                'verification_code' => $this->codes->generate(),
                'status' => CertificateStatus::Issued->value,
                'signature_name' => $settings->signature_name,
                'signature_title' => $settings->signature_title,
                'issued_at' => now(),
            ]);

            $certificate->forceFill(['signature_hash' => $this->signatures->hash($certificate)])->save();

            return [$certificate, true];
        });

        if ($created) {
            CertificateIssued::dispatch($certificate);
        }

        return $certificate;
    }
}
