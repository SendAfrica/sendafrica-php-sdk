<?php
/**
 * SendAfrica PHP SDK — OTP Verification Example
 *
 * This example shows a complete OTP flow:
 * 1. Generate and send OTP to user's phone
 * 2. User enters the code
 * 3. Verify the code
 *
 * In production, store the OTP in your database with an expiry time.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY_HERE');

// === STEP 1: Send OTP ===

$phone = '0712345678';

$otpResult = $client->sms->sendOtp($phone, length: 6, expiry: 10);

echo "OTP sent to {$phone}\n";
echo "Message ID: {$otpResult['message_id']}\n";

// Store the OTP in your database (NEVER log or expose it in production)
$storedOtp = $otpResult['otp'];
echo "OTP code (store this in DB): {$storedOtp}\n";

// === STEP 2: User enters the code ===

// Simulating user input — in real app this comes from $_POST or $_GET
$userInput = $storedOtp; // Pretend the user entered the correct code

// === STEP 3: Verify ===

if ($client->sms->verifyOtp($userInput, $storedOtp)) {
    echo "✓ Phone number verified!\n";
    // Mark as verified in your database
} else {
    echo "✗ Invalid code. Please try again.\n";
}
