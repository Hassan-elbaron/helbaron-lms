<?php

namespace App\Domains\Certification\Events;

use App\Domains\Certification\Models\Certificate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CertificateIssued
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Certificate $certificate) {}
}
