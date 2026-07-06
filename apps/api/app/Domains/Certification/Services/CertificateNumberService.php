<?php

namespace App\Domains\Certification\Services;

use App\Domains\Certification\Models\Certificate;
use App\Shared\Services\BaseService;

/**
 * Allocates a unique, human-readable certificate number, e.g. CERT-2025-000123.
 */
class CertificateNumberService extends BaseService
{
    public function next(): string
    {
        $prefix = (string) config('certification.number.prefix', 'CERT');
        $year = now()->format('Y');
        $sequence = Certificate::whereYear('created_at', $year)->withTrashed()->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}
