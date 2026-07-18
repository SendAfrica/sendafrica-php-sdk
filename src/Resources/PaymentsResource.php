<?php

declare(strict_types=1);

namespace SendAfrica\Resources;

use SendAfrica\HttpClient;
use SendAfrica\Models\Payment;
use SendAfrica\Models\VoucherRate;

class PaymentsResource
{
    private HttpClient $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a credit top-up payment.
     *
     * @param int    $amount   Top-up amount in TZS (must be positive)
     * @param string $provider Payment provider ("manual", "snippe", etc.)
     * @param string|null $phone Required for mobile-money providers, ignored for "manual"
     */
    public function create(int $amount, string $provider = 'manual', ?string $phone = null): Payment
    {
        $payload = [
            'amount' => $amount,
            'provider' => $provider,
        ];

        if ($phone !== null) {
            $payload['phone'] = $phone;
        }

        $response = $this->http->post('/vouchers', $payload);
        return new Payment($response['data'] ?? $response);
    }

    /**
     * Fetch the current pricing schedule: minimum top-up amount and
     * the tiered TZS-per-credit rate table.
     */
    public function rate(): VoucherRate
    {
        $response = $this->http->get('/vouchers/rate');
        return new VoucherRate($response['data'] ?? $response);
    }
}
