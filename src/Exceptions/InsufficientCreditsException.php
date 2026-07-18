<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class InsufficientCreditsException extends SendAfricaException
{
    public function __construct(string $message = 'Insufficient credits', ?string $requestId = null)
    {
        parent::__construct($message, 'insufficient_credits', $requestId, 402);
    }
}
