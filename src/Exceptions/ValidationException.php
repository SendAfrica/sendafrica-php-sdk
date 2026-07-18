<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class ValidationException extends SendAfricaException
{
    public function __construct(string $message = 'Validation error', ?string $requestId = null)
    {
        parent::__construct($message, 'validation_error', $requestId, 400);
    }
}
