<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class NotFoundException extends SendAfricaException
{
    public function __construct(string $message = 'Resource not found', ?string $requestId = null)
    {
        parent::__construct($message, 'not_found', $requestId, 404);
    }
}
