<?php

declare(strict_types=1);

namespace SendAfrica\Exception;

class InsufficientCreditsException extends SendAfricaException
{
    public function __construct(string $message = 'Insufficient credits to send SMS', ?string $requestId = null)
    {
        parent::__construct($message, 'insufficient_credits', $requestId, 402);
    }
}
