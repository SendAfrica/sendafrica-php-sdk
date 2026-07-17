# SendAfrica PHP SDK

Send SMS messages from your PHP app in minutes. This SDK wraps the [SendAfrica API](https://sendafrica.online) so you don't have to deal with HTTP requests, headers, or JSON parsing yourself.

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

Run this one command in your terminal:

```bash
composer require sendafrica/php-sdk
```

This downloads the SDK and sets up autoloading automatically. You'll see a `vendor/` folder appear in your project.

---

## Step 3 — Create Your First PHP File

Create a file called `test-sms.php` and paste this code:

```php
<?php

// This line loads the SDK automatically
require_once 'vendor/autoload.php';

// Import the SendAfrica client
use SendAfrica\Client;

// Replace with YOUR API key from the dashboard
$apiKey = 'paste-your-api-key-here';

// Create the client
$client = new Client($apiKey);

// Send an SMS
$result = $client->send('0712345678', 'Hello from my app!');

// Print the result
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

You should see:

```
SMS sent!
Message ID: SA-19bd8ee5-b843-49d8-ab16-ed4e04a96fcf
Credits used: 1
```

And the phone receives the SMS. That's it, you're done.

---

## Common Tasks

### Send an SMS with a Sender ID

A sender ID is the name that appears as the sender on the recipient's phone (e.g. "MyShop" instead of a random number).

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

$result = $client->sms->send(
    to: '0712345678',
    message: 'Your order #1234 is confirmed!',
    from: 'MyShop'
);

echo "Sent! Message ID: " . $result['message_id'];
```

---

### Send SMS to Multiple People at Once

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

$recipients = [
    '0712345678',
    '0754000111',
    '0789012345',
];

$result = $client->sms->sendBulk(
    to: $recipients,
    message: 'Flash sale! 50% off everything today.',
    from: 'MyShop'
);

echo "Total: " . $result['total'] . "\n";
echo "Sent: " . $result['sent'] . "\n";
echo "Failed: " . $result['failed'] . "\n";
```

---

### OTP Verification (Step by Step)

OTP means "One-Time Password" — a short code sent to the user's phone to verify they own that number. Here's the full flow:

#### Step A — Send the OTP when the user clicks "Verify"

```php
<?php
// send-otp.php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

$phone = $_POST['phone']; // e.g. "0712345678"

// This sends a 6-digit code via SMS
$otp = $client->sms->sendOtp($phone);

// Store the code in your database so you can check it later
// In real code, save $otp['otp'] to your database with the user's ID
saveToDatabase($otp['otp']);

echo "Code sent to your phone!";
```

#### Step B — Verify the code the user entered

```php
<?php
// verify-otp.php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

$phone = $_POST['phone'];
$code  = $_POST['code']; // What the user typed in

// Get the stored code from your database
$storedCode = getFromDatabase($phone);

// Check if it matches
if ($client->sms->verifyOtp($code, $storedCode)) {
    echo "Phone verified!";

    // Mark the phone as verified in your database
    markPhoneAsVerified($phone);
} else {
    echo "Wrong code. Please try again.";
}
```

#### The HTML form for the user

```html
<!-- verify-form.html -->
<form method="POST" action="verify-otp.php">
    <input type="text" name="phone" placeholder="0712345678" required>
    <input type="text" name="code" placeholder="Enter 6-digit code" required>
    <button type="submit">Verify</button>
</form>
```

#### Customize the OTP

```php
// 4-digit code (e.g. for simple apps)
$otp = $client->sms->sendOtp('0712345678', length: 4);

// 8-digit code that expires in 15 minutes
$otp = $client->sms->sendOtp('0712345678', length: 8, expiry: 15);
```

---

### Check Your Credit Balance

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

// Quick way — returns just the number
$credits = $client->getBalance();
echo "You have {$credits} credits left.";
```

---

### See Your Credit History

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

$history = $client->credits->history(page: 1, perPage: 10);

foreach ($history['items'] as $tx) {
    echo $tx['description'] . ': ' . $tx['amount'] . " credits\n";
}
```

---

### See Available Credit Packages

```php
<?php
require_once 'vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY');

$packages = $client->credits->packages();

foreach ($packages as $pkg) {
    echo $pkg['name'] . ': ' . $pkg['credits'] . ' credits for '
         . $pkg['currency'] . ' ' . number_format($pkg['price']) . "\n";
}
```

---

## Handling Errors

Things can go wrong — wrong API key, not enough credits, bad phone number. Here's how to handle each case:

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
    // Wrong API key
    echo "Error: Invalid API key. Check your dashboard settings.";

} catch (InsufficientCreditsException $e) {
    // Ran out of credits
    echo "Error: Not enough credits. Buy more at app.sendafrica.online";

} catch (RateLimitException $e) {
    // Sending too fast
    echo "Error: Too many requests. Wait a moment and try again.";

} catch (ValidationException $e) {
    // Bad input (wrong phone number format, missing message, etc.)
    echo "Error: " . $e->getMessage();

} catch (SendAfricaException $e) {
    // Any other error
    echo "Error [" . $e->getErrorCode() . "]: " . $e->getMessage();
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

## Full Project Example

Here's a complete, real-world example. Create these files in your project:

### File structure

```
my-project/
├── composer.json
├── config.php
├── send-otp.php
├── verify-otp.php
└── vendor/              <-- created by composer
```

### config.php

```php
<?php
// Put your API key here. Never commit this file to git!
return [
    'sendafrica_api_key' => 'YOUR_API_KEY_HERE',
];
```

### send-otp.php

```php
<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

use SendAfrica\Client;

$config = require 'config.php';
$client = new Client($config['sendafrica_api_key']);

$phone = $_POST['phone'] ?? '';

if (empty($phone)) {
    die('Phone number is required.');
}

try {
    $otp = $client->sms->sendOtp($phone, length: 6, expiry: 10);

    // TODO: Save $otp['otp'] to your database
    // Example: $db->query("UPDATE users SET otp = '{$otp['otp']}' WHERE phone = ?", [$phone]);

    echo json_encode(['success' => true, 'message' => 'Code sent!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
```

### verify-otp.php

```php
<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

use SendAfrica\Client;

$config = require 'config.php';
$client = new Client($config['sendafrica_api_key']);

$phone = $_POST['phone'] ?? '';
$code  = $_POST['code'] ?? '';

if (empty($phone) || empty($code)) {
    die('Phone and code are required.');
}

// TODO: Get the stored OTP from your database
// Example: $row = $db->query("SELECT otp FROM users WHERE phone = ?", [$phone])->fetch();
// $storedCode = $row['otp'];
$storedCode = '123456'; // Replace with real DB lookup

if ($client->sms->verifyOtp($code, $storedCode)) {
    // TODO: Mark phone as verified in your database
    echo 'Phone verified!';
} else {
    echo 'Invalid code. Try again.';
}
```

---

## Quick Reference

| What you want to do | Code |
|---------------------|------|
| Send an SMS | `$client->send('0712345678', 'Hello')` |
| Send SMS with sender ID | `$client->sms->send('0712345678', 'Hello', 'MyBrand')` |
| Send SMS to many people | `$client->sms->sendBulk(['07...', '07...'], 'Hello')` |
| Send an OTP | `$client->sendOtp('0712345678')` |
| Verify an OTP | `$client->sms->verifyOtp($userInput, $storedCode)` |
| Check balance | `$client->getBalance()` |
| See credit history | `$client->credits->history()` |
| See credit packages | `$client->credits->packages()` |

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
