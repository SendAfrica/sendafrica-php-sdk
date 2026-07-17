# SendAfrica PHP SDK

Complete SMS auth system for PHP apps. Send SMS, handle OTP verification, login, registration, password reset, and phone verification — all in a few lines of code.

No need to build anything. Just install and use.

---

## Requirements

- PHP 7.4 or newer
- `ext-curl` enabled
- `ext-json` enabled
- A SendAfrica account — [sign up free](https://sendafrica.online)
- An API key — create one in **Settings > API Keys** in your dashboard

---

## Installation

```bash
composer require sendafrica/php-sdk
```

---

## Setup

Add your API key to your environment. **Never hardcode it in source code.**

### Using .env (recommended)

```bash
# .env
SENDAFRICA_API_KEY=your_api_key_here
```

```php
// config.php
return [
    'sendafrica_api_key' => getenv('SENDAFRICA_API_KEY'),
];
```

### Using a config file

```php
// config.php
return [
    'sendafrica_api_key' => 'your_api_key_here',
];
```

---

## Initialize the Client

Every file that sends SMS should start with this:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);
```

---

## Send SMS

```php
$client->send('0712345678', 'Your order #1234 is confirmed!');
```

With a sender ID:

```php
$client->sms->send('0712345678', 'Your order is ready!', 'MyShop');
```

Send to multiple recipients:

```php
$client->sms->sendBulk(
    to: ['0712345678', '0754000111', '0789012345'],
    message: 'Flash sale today!',
    from: 'MyShop'
);
```

---

## User Registration

When a user signs up, send a verification code to their phone.

### Step 1 — Send the OTP

```php
<?php
// register.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'];
$name  = $_POST['name'];

if (empty($phone) || empty($name)) {
    http_response_code(400);
    die('All fields required.');
}

$otp = $client->auth->sendRegistrationOtp($phone);

// Store hashed OTP in your database
$hash = $client->auth->hash($otp['otp']);

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$stmt = $pdo->prepare("INSERT INTO users (phone, name, otp_hash, otp_created_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$phone, $name, $hash]);

echo json_encode(['success' => true, 'message' => 'Verification code sent']);
```

### Step 2 — Verify the code

```php
<?php
// verify-registration.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'];
$code  = $_POST['code'];

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$stmt = $pdo->prepare("SELECT otp_hash, otp_created_at FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found.');
}

$result = $client->auth->verifyWithExpiry(
    entered: $code,
    expected: $user['otp_hash'],
    createdAt: $user['otp_created_at'],
    expiryMinutes: 10
);

if ($result['valid']) {
    // Mark phone as verified
    $pdo->prepare("UPDATE users SET phone_verified = 1, otp_hash = NULL, otp_created_at = NULL WHERE phone = ?")->execute([$phone]);
    echo 'Account created!';
} else {
    echo $result['message'];
}
```

---

## Login (Two-Factor Auth)

After the user enters their password, send an OTP to their phone.

### Step 1 — Send login code

```php
<?php
// login.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone    = $_POST['phone'];
$password = $_POST['password'];

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    die('Invalid credentials.');
}

// Password correct — send OTP
$otp = $client->auth->sendLoginOtp($phone, appName: 'MyApp');

// Store hash in DB
$hash = $client->auth->hash($otp['otp']);
$pdo->prepare("UPDATE users SET login_otp_hash = ?, login_otp_created_at = NOW() WHERE id = ?")->execute([$hash, $user['id']]);

echo json_encode(['success' => true, 'message' => 'Login code sent to your phone']);
```

### Step 2 — Verify login code

```php
<?php
// verify-login.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'];
$code  = $_POST['code'];

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$stmt = $pdo->prepare("SELECT id, login_otp_hash, login_otp_created_at FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$result = $client->auth->verifyWithExpiry(
    entered: $code,
    expected: $user['login_otp_hash'],
    createdAt: $user['login_otp_created_at'],
    expiryMinutes: 10
);

if ($result['valid']) {
    // Clear OTP and create session
    $pdo->prepare("UPDATE users SET login_otp_hash = NULL, login_otp_created_at = NULL WHERE id = ?")->execute([$user['id']]);

    session_start();
    $_SESSION['user_id'] = $user['id'];

    echo 'Welcome back!';
} else {
    echo $result['message'];
}
```

---

## Forgot Password

### Step 1 — Request reset code

```php
<?php
// forgot-password.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'];

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Don't reveal whether the phone exists
    echo json_encode(['success' => true, 'message' => 'If that phone is registered, a reset code has been sent.']);
    exit();
}

$otp = $client->auth->sendPasswordResetOtp($phone);
$hash = $client->auth->hash($otp['otp']);

$pdo->prepare("UPDATE users SET reset_otp_hash = ?, reset_otp_created_at = NOW() WHERE id = ?")->execute([$hash, $user['id']]);

echo json_encode(['success' => true, 'message' => 'Reset code sent to your phone']);
```

### Step 2 — Reset password

```php
<?php
// reset-password.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone       = $_POST['phone'];
$code        = $_POST['code'];
$newPassword = $_POST['new_password'];

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$stmt = $pdo->prepare("SELECT id, reset_otp_hash, reset_otp_created_at FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$result = $client->auth->verifyWithExpiry($code, $user['reset_otp_hash'], $user['reset_otp_created_at']);

if ($result['valid']) {
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ?, reset_otp_hash = NULL, reset_otp_created_at = NULL WHERE id = ?")->execute([$newHash, $user['id']]);
    echo 'Password updated!';
} else {
    echo $result['message'];
}
```

---

## Verify Phone Number

Send a short 4-digit code to verify a user's phone number.

```php
<?php
// send-phone-otp.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'];

$otp = $client->auth->sendPhoneVerificationOtp($phone, length: 4);

$hash = $client->auth->hash($otp['otp']);
$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$pdo->prepare("UPDATE users SET phone_otp_hash = ?, phone_otp_created_at = NOW() WHERE phone = ?")->execute([$hash, $phone]);

echo json_encode(['success' => true]);
```

```php
<?php
// verify-phone.php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';
$client = new \SendAfrica\Client($config['sendafrica_api_key']);

$phone = $_POST['phone'];
$code  = $_POST['code'];

$pdo = new PDO('mysql:host=localhost;dbname=myapp', 'root', '');
$stmt = $pdo->prepare("SELECT phone_otp_hash, phone_otp_created_at FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$result = $client->auth->verifyWithExpiry($code, $user['phone_otp_hash'], $user['phone_otp_created_at']);

if ($result['valid']) {
    $pdo->prepare("UPDATE users SET phone_verified = 1, phone_otp_hash = NULL, phone_otp_created_at = NULL WHERE phone = ?")->execute([$phone]);
    echo 'Phone verified!';
} else {
    echo $result['message'];
}
```

---

## Transaction Confirmation

Send a code to confirm a payment or order:

```php
$otp = $client->auth->sendTransactionOtp($phone, details: 'order #1234');
```

---

## Custom Message

Write your own SMS with `{{code}}` as the OTP placeholder:

```php
$otp = $client->auth->sendCustomOtp(
    to: $phone,
    message: 'Your MyShop code is {{code}}. Do not share it.',
    length: 6,
    expiry: 10
);
```

---

## Error Handling

Always wrap SMS calls in try/catch:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use SendAfrica\Client;
use SendAfrica\Exception\AuthenticationException;
use SendAfrica\Exception\InsufficientCreditsException;
use SendAfrica\Exception\RateLimitException;
use SendAfrica\Exception\ValidationException;
use SendAfrica\Exception\SendAfricaException;

$config = require __DIR__ . '/config.php';
$client = new Client($config['sendafrica_api_key']);

try {
    $client->send('0712345678', 'Hello!');
} catch (AuthenticationException $e) {
    error_log('SendAfrica: Invalid API key');
} catch (InsufficientCreditsException $e) {
    error_log('SendAfrica: Out of credits — top up at app.sendafrica.online');
} catch (RateLimitException $e) {
    // Retry after a delay
    sleep(60);
    $client->send('0712345678', 'Hello!');
} catch (ValidationException $e) {
    error_log('SendAfrica: ' . $e->getMessage());
} catch (SendAfricaException $e) {
    error_log('SendAfrica error [' . $e->getErrorCode() . ']: ' . $e->getMessage());
}
```

---

## Check Balance

```php
$credits = $client->getBalance();
if ($credits < 10) {
    // Alert your ops team
    error_log("SendAfrica: Only {$credits} credits remaining");
}
```

---

## Phone Number Format

All these formats are accepted:

| Format | Example |
|--------|---------|
| Local | `0712345678` |
| International | `+255712345678` |
| Without + | `255712345678` |

---

## Quick Reference

| What you want to do | Code |
|---------------------|------|
| Send an SMS | `$client->send('0712345678', 'Hello')` |
| Send with sender ID | `$client->sms->send('0712345678', 'Hello', 'MyBrand')` |
| Send to many people | `$client->sms->sendBulk(['07...', '07...'], 'Hello')` |
| Registration OTP | `$client->auth->sendRegistrationOtp('0712345678')` |
| Login OTP | `$client->auth->sendLoginOtp('0712345678', 'MyApp')` |
| Password reset OTP | `$client->auth->sendPasswordResetOtp('0712345678')` |
| Phone verify OTP | `$client->auth->sendPhoneVerificationOtp('0712345678')` |
| Transaction OTP | `$client->auth->sendTransactionOtp('0712345678', 'order #1234')` |
| Custom message OTP | `$client->auth->sendCustomOtp('0712345678', 'Code: {{code}}')` |
| Verify OTP | `$client->auth->verify($entered, $expected)` |
| Verify with expiry | `$client->auth->verifyWithExpiry($entered, $hash, $createdAt)` |
| Hash OTP | `$client->auth->hash($otp)` |
| Verify against hash | `$client->auth->verifyHash($entered, $hash)` |
| Generate OTP only | `$client->auth->generate(6)` |
| Check balance | `$client->getBalance()` |

---

## Need Help?

- [SendAfrica Docs](https://docs.sendafrica.online)
- [SendAfrica Dashboard](https://app.sendafrica.online)
- WhatsApp: +255 692 069 230

---

## License

MIT
