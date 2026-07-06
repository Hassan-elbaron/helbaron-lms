<?php

namespace App\Domains\Crm\Exceptions;

class InvalidStageException extends CrmException
{
    protected string $errorCode = 'CRM_INVALID_STAGE';

    protected int $status = 422;

    public function __construct(string $message = "The stage does not belong to this lead's pipeline.", array $details = [])
    {
        parent::__construct($message, $details);
    }
}
