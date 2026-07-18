<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class InvalidPhoneException extends ValidationException
{
    public function __construct(string $message = 'Invalid phone number', ?string $requestId = null)
    {
        parent::__construct($message, $requestId);
        $this->errorCode = 'invalid_phone';
    }
}
