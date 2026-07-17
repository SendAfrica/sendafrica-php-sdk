<?php

declare(strict_types=1);

namespace SendAfrica;

class SmsService
{
    private HttpClient $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Send a single SMS message.
     *
     * @param string $to      Recipient phone number (e.g. "0712345678" or "+255712345678")
     * @param string $message The SMS text to send
     * @param string|null $from Optional sender ID (e.g. "MyBrand")
     * @return array{message_id: string, status: string, cost: string, credits_used: int}
     */
    public function send(string $to, string $message, ?string $from = null): array
    {
        $payload = [
            'to' => $to,
            'message' => $message,
        ];

        if ($from !== null) {
            $payload['from'] = $from;
        }

        $response = $this->http->post('/v1/sms/', $payload);
        return $response['data'];
    }

    /**
     * Send the same SMS to multiple recipients (max 100).
     *
     * @param string[] $to       Array of recipient phone numbers
     * @param string   $message  The SMS text to send
     * @param string|null $from  Optional sender ID
     * @return array{total: int, sent: int, failed: int, results: array}
     */
    public function sendBulk(array $to, string $message, ?string $from = null): array
    {
        $payload = [
            'to' => $to,
            'message' => $message,
        ];

        if ($from !== null) {
            $payload['from'] = $from;
        }

        $response = $this->http->post('/v1/sms/bulk', $payload);
        return $response['data'];
    }

    /**
     * Generate and send an OTP to a phone number.
     *
     * @param string $to       Recipient phone number
     * @param int    $length   OTP length (4-8 digits, default 6)
     * @param int    $expiry   OTP expiry in minutes (default 10)
     * @param string|null $from Optional sender ID
     * @return array{otp: string, message_id: string, status: string, credits_used: int}
     */
    public function sendOtp(string $to, int $length = 6, int $expiry = 10, ?string $from = null): array
    {
        $otp = $this->generateOtp($length);
        $message = "Your verification code is {$otp}. Valid for {$expiry} minutes. Do not share it.";

        $result = $this->send($to, $message, $from);

        return array_merge($result, ['otp' => $otp]);
    }

    /**
     * Verify a user-entered OTP against the expected code.
     */
    public function verifyOtp(string $entered, string $expected): bool
    {
        if (strlen($entered) !== strlen($expected)) {
            return false;
        }

        return hash_equals($expected, $entered);
    }

    /**
     * Generate a random numeric OTP string.
     */
    private function generateOtp(int $length): string
    {
        $max = (int) str_repeat('9', $length);
        $min = (int) str_repeat('9', $length - 1) . '0';
        $otp = random_int($min, $max);
        return (string) $otp;
    }
}
