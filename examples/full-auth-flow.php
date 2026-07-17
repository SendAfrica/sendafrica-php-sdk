<?php
/**
 * Full Auth Flow — Registration, Login, Forgot Password, Phone Verify
 *
 * Copy this file, change the API key, and you have a complete auth system.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

// ============================================================
// 1. REGISTRATION — Verify phone number on signup
// ============================================================

function registerUser(Client $client, string $phone, string $name): array
{
    // Send verification OTP
    $otp = $client->auth->sendRegistrationOtp($phone);

    // In real app: save hash to DB
    // $hash = $client->auth->hash($otp['otp']);
    // $db->query("INSERT INTO users (phone, name, otp, otp_created) VALUES (?, ?, ?, NOW())", [$phone, $name, $hash]);

    return [
        'success' => true,
        'message' => "Verification code sent to {$phone}",
        'otp' => $otp['otp'], // For demo only — don't return this in production!
    ];
}

// ============================================================
// 2. LOGIN — Two-factor auth via SMS
// ============================================================

function loginUser(Client $client, string $phone, string $appName = 'MyApp'): array
{
    $otp = $client->auth->sendLoginOtp($phone, $appName);

    // In real app: save hash to DB with user_id
    return [
        'success' => true,
        'message' => "Login code sent to {$phone}",
        'otp' => $otp['otp'],
    ];
}

// ============================================================
// 3. FORGOT PASSWORD — Send reset code
// ============================================================

function forgotPassword(Client $client, string $phone): array
{
    $otp = $client->auth->sendPasswordResetOtp($phone);

    // In real app: save hash to DB with user_id and expiry
    return [
        'success' => true,
        'message' => "Password reset code sent to {$phone}",
        'otp' => $otp['otp'],
    ];
}

// ============================================================
// 4. VERIFY PHONE NUMBER
// ============================================================

function verifyPhone(Client $client, string $phone): array
{
    $otp = $client->auth->sendPhoneVerificationOtp($phone, length: 4);

    return [
        'success' => true,
        'message' => "Phone verification code sent to {$phone}",
        'otp' => $otp['otp'],
    ];
}

// ============================================================
// 5. VERIFY ANY OTP — With expiry check
// ============================================================

function verifyCode(Client $client, string $entered, string $expected, string $createdAt): array
{
    $result = $client->auth->verifyWithExpiry($entered, $expected, $createdAt, expiryMinutes: 10);

    if ($result['valid']) {
        // OTP is correct and not expired
        // In real app: mark as verified, clear OTP from DB
        return ['success' => true, 'message' => 'Verified!'];
    }

    return ['success' => false, 'message' => $result['message']];
}

// ============================================================
// 6. SECURE STORAGE — Hash OTPs in your database
// ============================================================

// Generate and hash for storage
$otp = $client->auth->generate(6);
$hashedOtp = $client->auth->hash($otp);

// Verify user input against stored hash (never store plain OTPs!)
$isValid = $client->auth->verifyHash('123456', $hashedOtp);
// $isValid = true or false

// ============================================================
// DEMO — Run all flows
// ============================================================

$phone = '0712345678';

echo "=== SendAfrica Auth Demo ===\n\n";

$r1 = registerUser($client, $phone, 'John');
echo "Register: {$r1['message']} (OTP: {$r1['otp']})\n";

$r2 = loginUser($client, $phone, 'MyShop');
echo "Login: {$r2['message']} (OTP: {$r2['otp']})\n";

$r3 = forgotPassword($client, $phone);
echo "Reset: {$r3['message']} (OTP: {$r3['otp']})\n";

$r4 = verifyPhone($client, $phone);
echo "Phone: {$r4['message']} (OTP: {$r4['otp']})\n";

echo "\nSecure hash example: {$hashedOtp}\n";
echo "Verify '123456' against hash: " . ($isValid ? 'YES' : 'NO') . "\n";
