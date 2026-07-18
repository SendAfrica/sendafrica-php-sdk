<?php

declare(strict_types=1);

namespace SendAfrica\Exceptions;

class WebhookSignatureException extends SendAfricaException
{
    public function __construct(string $message = 'Webhook signature verification failed')
    {
        parent::__construct($message, 'webhook_signature_error');
    }
}
