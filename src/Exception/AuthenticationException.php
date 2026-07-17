<?php

declare(strict_types=1);

namespace SendAfrica\Exception;

class AuthenticationException extends SendAfricaException
{
    public function __construct(string $message = 'Invalid or missing API key', ?string $requestId = null)
    {
        parent::__construct($message, 'unauthorized', $requestId, 401);
    }
}
