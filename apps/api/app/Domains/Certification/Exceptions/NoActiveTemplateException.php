<?php

namespace App\Domains\Certification\Exceptions;

class NoActiveTemplateException extends CertificationException
{
    protected string $errorCode = 'CERT_NO_TEMPLATE';

    protected int $status = 422;

    public function __construct(string $message = 'No active certificate template is configured.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
