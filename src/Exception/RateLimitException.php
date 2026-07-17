<?php

declare(strict_types=1);

namespace SendAfrica\Exception;

class RateLimitException extends SendAfricaException
{
    public function __construct(string $message = 'Rate limit exceeded', ?string $requestId = null)
    {
        parent::__construct($message, 'rate_limit_exceeded', $requestId, 429);
    }
}
