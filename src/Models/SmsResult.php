<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class SmsResult
{
    public string $messageId;
    public string $status;
    public int $creditsUsed;
    public ?string $cost;
    public ?string $to;

    public function __construct(array $data)
    {
        $this->messageId = $data['message_id'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->creditsUsed = $data['credits_used'] ?? 0;
        $this->cost = $data['cost'] ?? null;
        $this->to = $data['to'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'status' => $this->status,
            'credits_used' => $this->creditsUsed,
            'cost' => $this->cost,
            'to' => $this->to,
        ];
    }
}
