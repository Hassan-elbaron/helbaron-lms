<?php

namespace App\Domains\Crm\Exceptions;

class SeatNotAssignedException extends CrmException
{
    protected string $errorCode = 'CRM_SEAT_NOT_ASSIGNED';

    protected int $status = 404;

    public function __construct(string $message = 'No active seat assignment was found.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
