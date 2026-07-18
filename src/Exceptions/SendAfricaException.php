<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

use RuntimeException;

class SendAfricaException extends RuntimeException
{
    protected string $errorCode;
    protected ?string $requestId;
    protected ?string $responseBody;

    public function __construct(
        string $message = '',
        string $errorCode = 'unknown_error',
        ?string $requestId = null,
        int $statusCode = 0,
        ?string $responseBody = null
    ) {
        $this->errorCode = $errorCode;
        $this->requestId = $requestId;
        $this->responseBody = $responseBody;

        parent::__construct($message, $statusCode);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getStatusCode(): ?int
    {
        $code = $this->getCode();
        return $code > 0 ? $code : null;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
