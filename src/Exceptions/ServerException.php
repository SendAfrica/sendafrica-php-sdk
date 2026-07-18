<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class ServerException extends SendAfricaException
{
    public function __construct(string $message = 'Server error', ?string $requestId = null, int $statusCode = 500)
    {
        parent::__construct($message, 'server_error', $requestId, $statusCode);
    }
}
