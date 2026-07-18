<?php
/**
 * Complete auth system: register, login, reset, phone verify.
 *
 * Run with: php full-auth-system.php
 *
 * In production, store hashed OTPs in your database.
 * This demo stores them in the session for simplicity.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;

session_start();
$client = new SendAfrica();

// ============================================================
// REGISTER
// ============================================================

if (isset($_POST['register_phone'])) {
    $phone = trim($_POST['register_phone']);
    $name = trim($_POST['register_name']);

    $otp = $client->auth->sendRegistrationOtp($phone);
    $hash = $client->auth->hash($otp['otp']);

    $_SESSION['reg_hash'] = $hash;
    $_SESSION['reg_phone'] = $phone;
    $_SESSION['reg_name'] = $name;

    echo "Registration OTP sent to {$phone}\n";
    echo "Enter code: ";
    $input = trim(fgets(STDIN));

    $result = $client->auth->verifyWithExpiry($input, $hash, date('c'));
    if ($result['valid']) {
        echo "Registration successful!\n";
        unset($_SESSION['reg_hash']);
    } else {
        echo "Failed: {$result['message']}\n";
    }
}

// ============================================================
// LOGIN
// ============================================================

if (isset($_POST['login_phone'])) {
    $phone = trim($_POST['login_phone']);

    $otp = $client->auth->sendLoginOtp($phone, appName: 'MyApp');
    $hash = $client->auth->hash($otp['otp']);

    echo "Login OTP sent to {$phone}\n";
    echo "Enter code: ";
    $input = trim(fgets(STDIN));

    $result = $client->auth->verifyWithExpiry($input, $hash, date('c'), expiryMinutes: 5);
    if ($result['valid']) {
        echo "Welcome back!\n";
    } else {
        echo "Failed: {$result['message']}\n";
    }
}

// ============================================================
// FORGOT PASSWORD
// ============================================================

if (isset($_POST['reset_phone'])) {
    $phone = trim($_POST['reset_phone']);

    $otp = $client->auth->sendPasswordResetOtp($phone);
    $hash = $client->auth->hash($otp['otp']);

    echo "Password reset OTP sent to {$phone}\n";
    echo "Enter code: ";
    $input = trim(fgets(STDIN));

    $result = $client->auth->verifyWithExpiry($input, $hash, date('c'));
    if ($result['valid']) {
        echo "Password reset successful!\n";
    } else {
        echo "Failed: {$result['message']}\n";
    }
}

// ============================================================
// PHONE VERIFICATION
// ============================================================

if (isset($_POST['verify_phone'])) {
    $phone = trim($_POST['verify_phone']);

    $otp = $client->auth->sendPhoneVerificationOtp($phone, length: 4);
    $hash = $client->auth->hash($otp['otp']);

    echo "Phone verification OTP sent to {$phone}\n";
    echo "Enter code: ";
    $input = trim(fgets(STDIN));

    $result = $client->auth->verifyWithExpiry($input, $hash, date('c'));
    if ($result['valid']) {
        echo "Phone verified!\n";
    } else {
        echo "Failed: {$result['message']}\n";
    }
}
