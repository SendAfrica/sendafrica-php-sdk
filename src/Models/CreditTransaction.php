<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class CreditTransaction
{
    public string $id;
    public string $type;
    public int $amount;
    public int $balanceAfter;
    public ?string $description;
    public ?string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->type = $data['type'] ?? '';
        $this->amount = $data['amount'] ?? 0;
        $this->balanceAfter = $data['balance_after'] ?? 0;
        $this->description = $data['description'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'balance_after' => $this->balanceAfter,
            'description' => $this->description,
            'created_at' => $this->createdAt,
        ];
    }
}
