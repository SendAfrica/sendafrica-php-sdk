<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class RateTier
{
    public int $maxAmountTzs;
    public int $rateTzsPerCredit;

    public function __construct(array $data)
    {
        $this->maxAmountTzs = $data['max_amount_tzs'] ?? 0;
        $this->rateTzsPerCredit = $data['rate_tzs_per_credit'] ?? 0;
    }

    public function toArray(): array
    {
        return [
            'max_amount_tzs' => $this->maxAmountTzs,
            'rate_tzs_per_credit' => $this->rateTzsPerCredit,
        ];
    }
}
