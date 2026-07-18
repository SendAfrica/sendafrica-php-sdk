<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class CreditBalance
{
    public string $accountId;
    public int $balance;

    public function __construct(array $data)
    {
        $this->accountId = $data['account_id'] ?? '';
        $this->balance = $data['balance'] ?? 0;
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'balance' => $this->balance,
        ];
    }
}
