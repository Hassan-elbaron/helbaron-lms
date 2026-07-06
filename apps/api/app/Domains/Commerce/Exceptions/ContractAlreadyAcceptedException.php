<?php

namespace App\Domains\Commerce\Exceptions;

class ContractAlreadyAcceptedException extends CommerceException
{
    protected string $errorCode = 'COMMERCE_CONTRACT_ALREADY_ACCEPTED';

    protected int $status = 409;

    public function __construct(string $message = 'This contract has already been accepted.', array $details = [])
    {
        parent::__construct($message, $details);
    }
}
