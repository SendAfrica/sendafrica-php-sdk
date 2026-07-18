<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class Payment
{
    public string $id;
    public string $status;
    public ?int $amount;
    public ?int $creditAmount;
    public string $currency;
    public ?string $provider;
    public ?string $source;
    public ?string $createdAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->status = $data['status'] ?? '';
        $this->amount = $data['amount'] ?? null;
        $this->creditAmount = $data['credit_amount'] ?? null;
        $this->currency = $data['currency'] ?? 'TZS';
        $this->provider = $data['provider'] ?? null;
        $this->source = $data['source'] ?? null;
        $this->createdAt = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'amount' => $this->amount,
            'credit_amount' => $this->creditAmount,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'source' => $this->source,
            'created_at' => $this->createdAt,
        ];
    }
}
