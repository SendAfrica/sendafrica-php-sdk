<?php

declare(strict_types=1);

namespace SendAfrica;

/**
 * SendAfrica PHP SDK
 *
 * Usage:
 *   $client = new SendAfrica\Client('your-api-key');
 *
 *   // Send SMS
 *   $result = $client->sms->send('0712345678', 'Hello from my app!');
 *
 *   // Send OTP
 *   $otp = $client->sms->sendOtp('0712345678');
 *   // Store $otp['otp'] in your database, verify later
 *
 *   // Check balance
 *   $balance = $client->credits->getBalance();
 *
 * @see https://docs.sendafrica.online — Full API documentation
 */
class Client
{
    public SmsService $sms;
    public CreditsService $credits;

    private HttpClient $http;

    /**
     * @param string $apiKey  Your SendAfrica API key
     * @param string $baseUrl API base URL (default: https://api.sendafrica.online)
     * @param int    $timeout Request timeout in seconds (default: 30)
     */
    public function __construct(string $apiKey, string $baseUrl = 'https://api.sendafrica.online', int $timeout = 30)
    {
        $this->http = new HttpClient($apiKey, $baseUrl, $timeout);
        $this->sms = new SmsService($this->http);
        $this->credits = new CreditsService($this->http);
    }

    /**
     * Quick helper: send SMS in one line.
     *
     * @example $client->send('0712345678', 'Hello!');
     */
    public function send(string $to, string $message, ?string $from = null): array
    {
        return $this->sms->send($to, $message, $from);
    }

    /**
     * Quick helper: send OTP in one line.
     *
     * @example $otp = $client->sendOtp('0712345678');
     */
    public function sendOtp(string $to, int $length = 6, int $expiry = 10, ?string $from = null): array
    {
        return $this->sms->sendOtp($to, $length, $expiry, $from);
    }

    /**
     * Quick helper: check balance in one line.
     *
     * @example $credits = $client->getBalance();
     */
    public function getBalance(): int
    {
        return $this->credits->getBalance();
    }
}
