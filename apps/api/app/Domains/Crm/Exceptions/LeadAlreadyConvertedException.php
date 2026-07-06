<?php

namespace App\Domains\Crm\Exceptions;

class LeadAlreadyConvertedException extends CrmException
{
    protected string $errorCode = 'CRM_LEAD_ALREADY_CONVERTED';

    protected int $status = 409;

    public function __construct(string $message = 'This lead has already been converted.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
