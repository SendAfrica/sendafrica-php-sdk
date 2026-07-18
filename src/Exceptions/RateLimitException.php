<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class RateLimitException extends SendAfricaException
{
    protected ?float $retryAfter;

    public function __construct(string $message = 'Rate limit exceeded', ?string $requestId = null, ?float $retryAfter = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($message, 'rate_limit_exceeded', $requestId, 429);
    }

    public function getRetryAfter(): ?float
    {
        return $this->retryAfter;
    }
}
