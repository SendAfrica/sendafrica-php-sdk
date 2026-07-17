<?php

declare(strict_types=1);

namespace SendAfrica\Exception;

class ServerException extends SendAfricaException
{
    public function __construct(string $message = 'Server error', ?string $requestId = null)
    {
        parent::__construct($message, 'server_error', $requestId, 500);
    }
}
