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
 *   $client->send('0712345678', 'Hello!');
 *
 *   // Full auth system
 *   $otp = $client->auth->sendRegistrationOtp('0712345678');
 *   $client->auth->verify($userInput, $otp['otp']);
 *
 *   // Check balance
 *   $credits = $client->getBalance();
 *
 * @see https://docs.sendafrica.online — Full API documentation
 */
class Client
{
    public SmsService $sms;
    public CreditsService $credits;
    public AuthService $auth;

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
        $this->auth = new AuthService($this->sms);
    }

    /**
     * Quick helper: send SMS in one line.
     */
    public function send(string $to, string $message, ?string $from = null): array
    {
        return $this->sms->send($to, $message, $from);
    }

    /**
     * Quick helper: send OTP in one line.
     */
    public function sendOtp(string $to, int $length = 6, int $expiry = 10, ?string $from = null): array
    {
        return $this->sms->sendOtp($to, $length, $expiry, $from);
    }

    /**
     * Quick helper: check balance in one line.
     */
    public function getBalance(): int
    {
        return $this->credits->getBalance();
    }
}
