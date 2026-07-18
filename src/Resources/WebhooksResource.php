<?php

declare(strict_types=1);

namespace SendAfrica\Resources;

use SendAfrica\Exceptions\WebhookSignatureException;
use SendAfrica\Models\WebhookEvent;

class WebhooksResource
{
    private ?string $webhookSecret;

    public function __construct(?string $webhookSecret = null)
    {
        $this->webhookSecret = $webhookSecret;
    }

    /**
     * Parse and optionally verify an incoming webhook payload using HMAC-SHA256.
     *
     * @param string|resource $payload Raw webhook body
     * @param string|null     $signature Value from X-SendAfrica-Signature header
     * @param string|null     $secret    Overrides the client-level webhook_secret
     */
    public function parse($payload, ?string $signature = null, ?string $secret = null): WebhookEvent
    {
        if (is_resource($payload)) {
            $payload = stream_get_contents($payload);
        }

        $body = is_string($payload) ? $payload : (string) $payload;

        $effectiveSecret = $secret ?? $this->webhookSecret;

        if ($signature !== null && $effectiveSecret !== null) {
            $expected = hash_hmac('sha256', $body, $effectiveSecret);
            if (!hash_equals($expected, $signature)) {
                throw new WebhookSignatureException(
                    "Webhook signature mismatch. Expected: {$expected}, got: {$signature}"
                );
            }
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new WebhookSignatureException('Invalid webhook payload: not valid JSON');
        }

        $type = $data['type'] ?? '';
        $messageId = $data['message_id'] ?? $data['data']['message_id'] ?? null;

        return new WebhookEvent($type, $messageId, $data);
    }
}
