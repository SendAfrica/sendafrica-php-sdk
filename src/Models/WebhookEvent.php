<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class WebhookEvent
{
    public string $type;
    public ?string $messageId;
    public array $data;

    public function __construct(string $type, ?string $messageId, array $data)
    {
        $this->type = $type;
        $this->messageId = $messageId;
        $this->data = $data;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message_id' => $this->messageId,
            'data' => $this->data,
        ];
    }
}
