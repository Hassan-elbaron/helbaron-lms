<?php

namespace App\Domains\Crm\Exceptions;

class SeatPoolExhaustedException extends CrmException
{
    protected string $errorCode = 'CRM_SEATS_EXHAUSTED';

    protected int $status = 409;

    public function __construct(string $message = 'No seats are available in this pool.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
