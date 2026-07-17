<?php

declare(strict_types=1);

namespace SendAfrica\Exception;

use RuntimeException;

class SendAfricaException extends RuntimeException
{
    protected string $errorCode;
    protected ?string $requestId;

    public function __construct(string $message, string $errorCode = 'unknown_error', ?string $requestId = null, int $httpStatus = 0)
    {
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;

        parent::__construct("[{$errorCode}] {$message}", $httpStatus);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }
}
