<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class VoucherRate
{
    public int $minAmountTzs;

    /** @var RateTier[] */
    public array $tiers;

    /**
     * @param array{min_amount_tzs: int, tiers: array[]} $data
     */
    public function __construct(array $data)
    {
        $this->minAmountTzs = $data['min_amount_tzs'] ?? 0;
        $this->tiers = array_map(
            fn(array $t) => new RateTier($t),
            $data['tiers'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'min_amount_tzs' => $this->minAmountTzs,
            'tiers' => array_map(fn(RateTier $t) => $t->toArray(), $this->tiers),
        ];
    }
}
