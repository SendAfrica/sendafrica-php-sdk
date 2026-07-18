<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class ConnectionException extends SendAfricaException
{
    public function __construct(string $message = 'Connection error', ?\Throwable $previous = null)
    {
        parent::__construct($message, 'connection_error', null, 0, null);
        if ($previous !== null) {
            $this->previous = $previous;
        }
    }
}
