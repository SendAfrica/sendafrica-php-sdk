<?php

declare(strict_types=1);

namespace SendAfrica\Models;

class SmsAnalysis
{
    public string $encoding;
    public int $characters;
    public int $parts;
    public int $credits;

    public function __construct(string $encoding, int $characters, int $parts, int $credits)
    {
        $this->encoding = $encoding;
        $this->characters = $characters;
        $this->parts = $parts;
        $this->credits = $credits;
    }

    public function toArray(): array
    {
        return [
            'encoding' => $this->encoding,
            'characters' => $this->characters,
            'parts' => $this->parts,
            'credits' => $this->credits,
        ];
    }
}
