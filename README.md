# SendAfrica PHP SDK

Complete SMS auth system for your PHP app. Send SMS, handle OTP verification, login, registration, password reset, and phone verification — all in a few lines of code.

No need to build anything. Just install and use.

---

## What You Need Before Starting

1. **A SendAfrica account** — Sign up free at [sendafrica.online](https://sendafrica.online)
2. **An API key** — After signing up, go to **Settings > API Keys** in your dashboard and create one
3. **PHP 7.4 or newer** — Check by running `php -v` in your terminal
4. **Composer** — PHP's package manager. Check if you have it: `composer --version`

> Don't have Composer? Install it first: https://getcomposer.org

---

## Step 1 — Open Your Terminal

All commands in this guide are run in your **terminal** (also called command prompt on Windows).

Navigate to your PHP project folder:

```bash
cd /path/to/your/php-project
```

---

## Step 2 — Install the SDK

Run this one command:

```bash
composer require sendafrica/php-sdk
```

This downloads the SDK and sets up autoloading automatically. You'll see a `vendor/` folder appear in your project.

---

## Step 3 — Create Your First PHP File

Create a file called `test-sms.php` and paste this code:

```php
<?php

require_once 'vendor/autoload.php';

use SendAfrica\Client;

// Replace with YOUR API key from the dashboard
$client = new Client('paste-your-api-key-here');

// Send an SMS
$result = $client->send('0712345678', 'Hello from my app!');

echo "SMS sent!\n";
echo "Message ID: " . $result['message_id'] . "\n";
echo "Credits used: " . $result['credits_used'] . "\n";
```

Replace:
- `'paste-your-api-key-here'` with your actual API key
- `'0712345678'` with a real phone number

Run it:

```bash
php test-sms.php
```

That's it — you're sending SMS.

---

## Complete Auth System

The SDK includes a full auth service. No need to build OTP generation, messages, or verification yourself.

### Register a User (Phone Verification)

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');
$phone = '0712345678';

// Sends a 6-digit OTP to the user's phone
$otp = $client->auth->sendRegistrationOtp($phone);

// Save to your database (hash it, don't store plain text)
$hash = $client->auth->hash($otp['otp']);
// $db->query("INSERT INTO users (phone, otp, otp_created_at) VALUES (?, ?, NOW())", [$phone, $hash]);

echo "Code sent to {$phone}!";
```

### Verify the Code They Entered

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

$entered = $_POST['code']; // What the user typed
$expectedHash = '$2y$10$...'; // From your database
$createdAt = '2026-07-17 10:00:00'; // From your database

$result = $client->auth->verifyWithExpiry($entered, $expectedHash, $createdAt, expiryMinutes: 10);

if ($result['valid']) {
    echo "Phone verified!";
    // Mark as verified in your database
} else {
    echo $result['message']; // "OTP has expired" or "Invalid code"
}
```

### Login (Two-Factor Auth)

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');
$phone = '0712345678';

// Send login code
$otp = $client->auth->sendLoginOtp($phone, appName: 'MyShop');

// Save hash to DB, then verify when user enters it
$hash = $client->auth->hash($otp['otp']);
```

### Forgot Password

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');
$phone = '0712345678';

// Send password reset code
$otp = $client->auth->sendPasswordResetOtp($phone);

// Save hash to DB, verify when user enters it, then let them set new password
$hash = $client->auth->hash($otp['otp']);
```

### Verify Phone Number

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');
$phone = '0712345678';

// Sends a 4-digit code (shorter for phone verification)
$otp = $client->auth->sendPhoneVerificationOtp($phone, length: 4);
```

### Transaction / Order Confirmation

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');
$phone = '0712345678';

// Send confirmation code for a payment or order
$otp = $client->auth->sendTransactionOtp($phone, details: 'order #1234');
```

### Custom Message

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

// Write your own message — use {{code}} where the OTP goes
$otp = $client->auth->sendCustomOtp(
    to: '0712345678',
    message: 'Hey! Your MyShop code is {{code}}. Expires in 5 min.',
    length: 6,
    expiry: 5
);
```

### Simple Verify (Without Expiry Check)

```php
// Just check if the code matches — no expiry logic
if ($client->auth->verify($userInput, $storedOtp)) {
    echo "Correct!";
} else {
    echo "Wrong code.";
}
```

---

## Send SMS (Raw)

If you just want to send a message without auth features:

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

// Simple send
$client->send('0712345678', 'Your order is confirmed!');

// With sender ID
$client->sms->send('0712345678', 'Hello!', 'MyBrand');

// Send to many people at once
$client->sms->sendBulk(
    to: ['0712345678', '0754000111', '0789012345'],
    message: 'Flash sale today!',
    from: 'MyShop'
);
```

---

## Check Balance

```php
$credits = $client->getBalance();
echo "You have {$credits} credits left.";
```

---

## Error Handling

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;
use SendAfrica\Exception\AuthenticationException;
use SendAfrica\Exception\InsufficientCreditsException;
use SendAfrica\Exception\RateLimitException;
use SendAfrica\Exception\ValidationException;
use SendAfrica\Exception\SendAfricaException;

$client = new Client('YOUR_API_KEY');

try {
    $client->send('0712345678', 'Hello!');
} catch (AuthenticationException $e) {
    echo "Invalid API key.";
} catch (InsufficientCreditsException $e) {
    echo "Not enough credits.";
} catch (RateLimitException $e) {
    echo "Too many requests. Slow down.";
} catch (ValidationException $e) {
    echo "Error: " . $e->getMessage();
} catch (SendAfricaException $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## Full Auth Flow Example

Here's a complete registration + login system in 3 files:

### File structure

```
my-project/
├── composer.json
├── config.php
├── register.php
├── verify.php
├── login.php
├── login-verify.php
├── forgot-password.php
└── reset-password.php
```

### config.php

```php
<?php
return [
    'sendafrica_api_key' => 'YOUR_API_KEY_HERE',
];
```

### register.php

```php
<?php
require_once 'vendor/autoload.php';
$config = require 'config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'] ?? '';
$name  = $_POST['name'] ?? '';

if (empty($phone) || empty($name)) {
    die('All fields required.');
}

$otp = $client->auth->sendRegistrationOtp($phone);

// TODO: Save to database
// $hash = $client->auth->hash($otp['otp']);
// $db->query("INSERT INTO users (phone, name, otp_hash, otp_created_at) VALUES (?, ?, ?, NOW())", [$phone, $name, $hash]);

echo "Code sent to {$phone}! Check your phone.";
```

### verify.php

```php
<?php
require_once 'vendor/autoload.php';
$config = require 'config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'] ?? '';
$code  = $_POST['code'] ?? '';

// TODO: Get from database
// $row = $db->query("SELECT otp_hash, otp_created_at FROM users WHERE phone = ?", [$phone])->fetch();
$storedHash = '$2y$10$...'; // from DB
$createdAt  = '2026-07-17 10:00:00'; // from DB

$result = $client->auth->verifyWithExpiry($code, $storedHash, $createdAt, expiryMinutes: 10);

if ($result['valid']) {
    // TODO: Mark phone as verified, clear OTP from DB
    echo "Registration complete!";
} else {
    echo $result['message'];
}
```

### login.php

```php
<?php
require_once 'vendor/autoload.php';
$config = require 'config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'] ?? '';

// TODO: Check if user exists, get password, verify it, then:
$otp = $client->auth->sendLoginOtp($phone, appName: 'MyApp');

// TODO: Save hash to DB
echo "Login code sent to {$phone}!";
```

### login-verify.php

```php
<?php
require_once 'vendor/autoload.php';
$config = require 'config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'] ?? '';
$code  = $_POST['code'] ?? '';

// TODO: Get hash and created_at from DB
$storedHash = '$2y$10$...';
$createdAt  = '2026-07-17 10:00:00';

$result = $client->auth->verifyWithExpiry($code, $storedHash, $createdAt, expiryMinutes: 5);

if ($result['valid']) {
    // TODO: Create session, log user in
    session_start();
    $_SESSION['user_id'] = 123; // from DB
    echo "Welcome back!";
} else {
    echo $result['message'];
}
```

### forgot-password.php

```php
<?php
require_once 'vendor/autoload.php';
$config = require 'config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'] ?? '';

// TODO: Check if user exists
$otp = $client->auth->sendPasswordResetOtp($phone);

// TODO: Save hash to DB
echo "Reset code sent to {$phone}!";
```

### reset-password.php

```php
<?php
require_once 'vendor/autoload.php';
$config = require 'config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone       = $_POST['phone'] ?? '';
$code        = $_POST['code'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

// TODO: Get hash and created_at from DB
$storedHash = '$2y$10$...';
$createdAt  = '2026-07-17 10:00:00';

$result = $client->auth->verifyWithExpiry($code, $storedHash, $createdAt, expiryMinutes: 10);

if ($result['valid']) {
    // TODO: Update password in DB, clear OTP
    echo "Password updated!";
} else {
    echo $result['message'];
}
```

---

## Phone Number Rules

All these formats are accepted:

| You type | Example | It works? |
|----------|---------|-----------|
| Local format | `0712345678` | Yes |
| With country code | `+255712345678` | Yes |
| Without the + | `255712345678` | Yes |

You don't need to worry about formatting — just give it the number and the API handles it.

---

## Quick Reference

| What you want to do | Code |
|---------------------|------|
| Send an SMS | `$client->send('0712345678', 'Hello')` |
| Send SMS with sender ID | `$client->sms->send('0712345678', 'Hello', 'MyBrand')` |
| Send SMS to many people | `$client->sms->sendBulk(['07...', '07...'], 'Hello')` |
| Registration OTP | `$client->auth->sendRegistrationOtp('0712345678')` |
| Login OTP | `$client->auth->sendLoginOtp('0712345678', 'MyApp')` |
| Password reset OTP | `$client->auth->sendPasswordResetOtp('0712345678')` |
| Phone verify OTP | `$client->auth->sendPhoneVerificationOtp('0712345678')` |
| Transaction OTP | `$client->auth->sendTransactionOtp('0712345678', 'order #1234')` |
| Custom message OTP | `$client->auth->sendCustomOtp('0712345678', 'Your code is {{code}}')` |
| Verify OTP | `$client->auth->verify($entered, $expected)` |
| Verify with expiry | `$client->auth->verifyWithExpiry($entered, $hash, $createdAt)` |
| Hash OTP for storage | `$client->auth->hash($otp)` |
| Verify against hash | `$client->auth->verifyHash($entered, $hash)` |
| Generate OTP only | `$client->auth->generate(6)` |
| Check if expired | `$client->auth->isExpired($createdAt, expiryMinutes: 10)` |
| Check balance | `$client->getBalance()` |
| Credit history | `$client->credits->history()` |
| Credit packages | `$client->credits->packages()` |

---

## Requirements

- PHP 7.4 or newer
- The `curl` extension enabled (`php -m | grep curl`)
- The `json` extension enabled (`php -m | grep json`)

---

## Need Help?

- [SendAfrica Docs](https://docs.sendafrica.online)
- [SendAfrica Dashboard](https://app.sendafrica.online)
- WhatsApp: +255 692 069 230

---

## License

MIT — use it freely in your projects.
