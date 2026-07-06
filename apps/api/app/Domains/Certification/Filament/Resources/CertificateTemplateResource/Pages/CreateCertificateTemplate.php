<?php

namespace App\Domains\Certification\Filament\Resources\CertificateTemplateResource\Pages;

use App\Domains\Certification\Filament\Resources\CertificateTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCertificateTemplate extends CreateRecord
{
    protected static string $resource = CertificateTemplateResource::class;
}
