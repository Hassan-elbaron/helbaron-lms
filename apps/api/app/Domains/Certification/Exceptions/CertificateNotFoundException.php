<?php

namespace App\Domains\Certification\Exceptions;

class CertificateNotFoundException extends CertificationException
{
    protected string $errorCode = 'CERT_NOT_FOUND';

    protected int $status = 404;

    public function __construct(string $message = 'Certificate not found.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
