<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class BulkSmsResult
{
    /** @var SmsResult[] */
    public array $results;

    /** @var array<int, array{index: int, to: string, error: string}> */
    public array $failed;

    /**
     * @param SmsResult[] $results
     * @param array<int, array{index: int, to: string, error: string}> $failed
     */
    public function __construct(array $results = [], array $failed = [])
    {
        $this->results = $results;
        $this->failed = $failed;
    }

    public function getSentCount(): int
    {
        return count($this->results);
    }

    public function getFailedCount(): int
    {
        return count($this->failed);
    }

    public function toArray(): array
    {
        return [
            'results' => array_map(fn(SmsResult $r) => $r->toArray(), $this->results),
            'failed' => $this->failed,
            'sent_count' => $this->getSentCount(),
            'failed_count' => $this->getFailedCount(),
        ];
    }
}
