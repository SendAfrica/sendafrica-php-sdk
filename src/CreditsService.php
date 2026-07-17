<?php

declare(strict_types=1);

namespace SendAfrica;

class CreditsService
{
    private HttpClient $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Get the current credit balance.
     *
     * @return array{account_id: string, balance: int}
     */
    public function balance(): array
    {
        $response = $this->http->get('/v1/credits/balance');
        return $response['data'];
    }

    /**
     * Get the numeric balance only.
     */
    public function getBalance(): int
    {
        $data = $this->balance();
        return $data['balance'];
    }

    /**
     * Get credit transaction history.
     *
     * @param int $page     Page number (default 1)
     * @param int $perPage  Results per page (max 200, default 25)
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public function history(int $page = 1, int $perPage = 25): array
    {
        $response = $this->http->get('/v1/credits/history', [
            'page' => $page,
            'per_page' => $perPage,
        ]);
        return $response['data'];
    }

    /**
     * List available credit packages.
     *
     * @return array<int, array{id: string, name: string, credits: int, price: int, currency: string}>
     */
    public function packages(): array
    {
        $response = $this->http->get('/v1/packages');
        return $response['data'];
    }
}
