<?php

namespace App\Domains\Certification\Exceptions;

class PdfRenderFailedException extends CertificationException
{
    protected string $errorCode = 'CERT_PDF_FAILED';

    protected int $status = 500;

    public function __construct(string $message = 'The certificate PDF could not be generated.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
