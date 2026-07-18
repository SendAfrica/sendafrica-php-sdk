<?php

declare(strict_types=1);

namespace SendAfrica\Resources;

use SendAfrica\HttpClient;
use SendAfrica\Models\CreditBalance;
use SendAfrica\Models\CreditTransaction;

class CreditsResource
{
    private HttpClient $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Get the current credit balance.
     */
    public function balance(): CreditBalance
    {
        $response = $this->http->get('/credits/balance');
        return new CreditBalance($response['data'] ?? $response);
    }

    /**
     * Get credit transaction history with page-based pagination.
     *
     * @return CreditTransaction[]
     */
    public function history(int $page = 1, int $perPage = 25): array
    {
        $response = $this->http->get('/credits/history', [
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $items = $response['data']['items'] ?? $response['data'] ?? [];
        return array_map(fn(array $tx) => new CreditTransaction($tx), $items);
    }
}
