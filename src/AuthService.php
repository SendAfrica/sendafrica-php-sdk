<?php

declare(strict_types=1);

namespace SendAfrica;

/**
 * Full authentication service — handles every SMS-based auth flow.
 *
 * This gives developers a complete auth system out of the box.
 * No need to build OTP generation, message templates, or verification logic.
 */
class AuthService
{
    private SmsService $sms;

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Send OTP for user registration / email verification.
     *
     * @return array{otp: string, message_id: string, status: string, credits_used: int, expires_in: int}
     */
    public function sendRegistrationOtp(string $to, int $length = 6, int $expiry = 10, ?string $from = null): array
    {
        $otp = $this->generate($length);
        $message = "Welcome! Your verification code is {$otp}. Valid for {$expiry} minutes. Do not share it.";

        $result = $this->sms->send($to, $message, $from);

        return array_merge($result, [
            'otp' => $otp,
            'expires_in' => $expiry * 60,
        ]);
    }

    /**
     * Send OTP for login verification (two-factor auth).
     *
     * @return array{otp: string, message_id: string, status: string, credits_used: int, expires_in: int}
     */
    public function sendLoginOtp(string $to, string $appName = '', int $length = 6, int $expiry = 5, ?string $from = null): array
    {
        $otp = $this->generate($length);
        $prefix = $appName ? "Your {$appName}" : "Your";
        $message = "{$prefix} login code is {$otp}. Valid for {$expiry} minutes. If you didn't request this, ignore this message.";

        $result = $this->sms->send($to, $message, $from);

        return array_merge($result, [
            'otp' => $otp,
            'expires_in' => $expiry * 60,
        ]);
    }

    /**
     * Send OTP for password reset.
     *
     * @return array{otp: string, message_id: string, status: string, credits_used: int, expires_in: int}
     */
    public function sendPasswordResetOtp(string $to, int $length = 6, int $expiry = 10, ?string $from = null): array
    {
        $otp = $this->generate($length);
        $message = "Your password reset code is {$otp}. Valid for {$expiry} minutes. If you didn't request this, your account may be compromised.";

        $result = $this->sms->send($to, $message, $from);

        return array_merge($result, [
            'otp' => $otp,
            'expires_in' => $expiry * 60,
        ]);
    }

    /**
     * Send OTP to verify a phone number.
     *
     * @return array{otp: string, message_id: string, status: string, credits_used: int, expires_in: int}
     */
    public function sendPhoneVerificationOtp(string $to, int $length = 4, int $expiry = 10, ?string $from = null): array
    {
        $otp = $this->generate($length);
        $message = "Your phone verification code is {$otp}. Valid for {$expiry} minutes. Do not share it.";

        $result = $this->sms->send($to, $message, $from);

        return array_merge($result, [
            'otp' => $otp,
            'expires_in' => $expiry * 60,
        ]);
    }

    /**
     * Send OTP for order/transaction confirmation.
     *
     * @return array{otp: string, message_id: string, status: string, credits_used: int, expires_in: int}
     */
    public function sendTransactionOtp(string $to, string $details = '', int $length = 6, int $expiry = 5, ?string $from = null): array
    {
        $otp = $this->generate($length);
        $prefix = $details ? "Confirm {$details}" : "Confirm your transaction";
        $message = "{$prefix} with code {$otp}. Valid for {$expiry} minutes. Do not share it.";

        $result = $this->sms->send($to, $message, $from);

        return array_merge($result, [
            'otp' => $otp,
            'expires_in' => $expiry * 60,
        ]);
    }

    /**
     * Send a custom OTP with your own message.
     *
     * Use {{code}} as placeholder for the OTP in your message.
     *
     * @param string $to      Phone number
     * @param string $message Message template with {{code}} placeholder
     * @param int    $length  OTP length (4-8)
     * @param int    $expiry  Expiry in minutes
     * @param string|null $from Sender ID
     * @return array{otp: string, message_id: string, status: string, credits_used: int, expires_in: int}
     */
    public function sendCustomOtp(string $to, string $message, int $length = 6, int $expiry = 10, ?string $from = null): array
    {
        $otp = $this->generate($length);
        $finalMessage = str_replace('{{code}}', $otp, $message);

        $result = $this->sms->send($to, $finalMessage, $from);

        return array_merge($result, [
            'otp' => $otp,
            'expires_in' => $expiry * 60,
        ]);
    }

    /**
     * Verify an OTP code. Returns true if correct, false if wrong.
     */
    public function verify(string $entered, string $expected): bool
    {
        return $this->sms->verifyOtp($entered, $expected);
    }

    /**
     * Verify an OTP with expiry check.
     *
     * @param string $entered      The code the user entered
     * @param string $expected     The stored OTP code
     * @param string $createdAt    ISO 8601 timestamp when the OTP was created
     * @param int    $expiryMinutes How many minutes the OTP is valid for
     * @return array{valid: bool, expired: bool, message: string}
     */
    public function verifyWithExpiry(string $entered, string $expected, string $createdAt, int $expiryMinutes = 10): array
    {
        $created = new \DateTime($createdAt);
        $now = new \DateTime();
        $expiresAt = (clone $created)->modify("+{$expiryMinutes} minutes");

        if ($now > $expiresAt) {
            return [
                'valid' => false,
                'expired' => true,
                'message' => 'OTP has expired. Please request a new code.',
            ];
        }

        if (!$this->verifyHash($entered, $expected)) {
            return [
                'valid' => false,
                'expired' => false,
                'message' => 'Invalid code. Please try again.',
            ];
        }

        return [
            'valid' => true,
            'expired' => false,
            'message' => 'Verified successfully.',
        ];
    }

    /**
     * Check if an OTP has expired without verifying it.
     */
    public function isExpired(string $createdAt, int $expiryMinutes = 10): bool
    {
        $created = new \DateTime($createdAt);
        $now = new \DateTime();
        $expiresAt = (clone $created)->modify("+{$expiryMinutes} minutes");

        return $now > $expiresAt;
    }

    /**
     * Generate a numeric OTP without sending it.
     */
    public function generate(int $length = 6): string
    {
        $max = (int) str_repeat('9', $length);
        $min = (int) (str_repeat('9', $length - 1) . '0');
        $otp = random_int($min, $max);
        return (string) $otp;
    }

    /**
     * Hash an OTP for secure storage in your database.
     * Use this instead of storing the plain OTP.
     */
    public function hash(string $otp): string
    {
        return password_hash($otp, PASSWORD_DEFAULT);
    }

    /**
     * Verify a user-entered OTP against a hash stored in your database.
     */
    public function verifyHash(string $entered, string $hash): bool
    {
        return password_verify($entered, $hash);
    }
}
