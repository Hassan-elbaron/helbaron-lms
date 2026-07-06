<?php

namespace App\Domains\Certification\Exceptions;

class CertificateRevokedException extends CertificationException
{
    protected string $errorCode = 'CERT_REVOKED';

    protected int $status = 410;

    public function __construct(string $message = 'This certificate has been revoked.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
