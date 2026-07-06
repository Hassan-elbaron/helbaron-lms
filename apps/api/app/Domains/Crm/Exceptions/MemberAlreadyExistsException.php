<?php

namespace App\Domains\Crm\Exceptions;

class MemberAlreadyExistsException extends CrmException
{
    protected string $errorCode = 'CRM_MEMBER_EXISTS';

    protected int $status = 409;

    public function __construct(string $message = 'This member is already part of the organization.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
