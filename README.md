Want the fully working sample code? Grab it here: https://github.com/mohamed-sinani/sendafrica-test-sdk

# SendAfrica PHP SDK

A complete SMS authentication system for PHP applications. Install it, add your API key, and you have registration verification, two-factor login, password reset, and phone verification — all working out of the box.

---

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [User Registration with Phone Verification](#user-registration-with-phone-verification)
- [Login with Two-Factor Authentication](#login-with-two-factor-authentication)
- [Forgot Password / Reset Password](#forgot-password--reset-password)
- [Phone Number Verification](#phone-number-verification)
- [Sending Transactional SMS](#sending-transactional-sms)
- [Error Handling](#error-handling)
- [API Reference](#api-reference)
- [Phone Number Format](#phone-number-format)
- [Help & Support](#help--support)

---

## Installation

```bash
composer require sendafrica/php-sdk
```

---

## Configuration

Store your API key in an environment file. Never commit it to version control.

**.env**
```
SENDAFRICA_API_KEY=your_api_key_here
DB_HOST=localhost
DB_NAME=myapp
DB_USER=root
DB_PASS=
```

**config.php**
```php
<?php
return [
    'sendafrica_api_key' => getenv('SENDAFRICA_API_KEY'),
    'db_host' => getenv('DB_HOST'),
    'db_name' => getenv('DB_NAME'),
    'db_user' => getenv('DB_USER'),
    'db_pass' => getenv('DB_PASS'),
];
```

**bootstrap.php** — Load this at the top of every file:
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = require __DIR__ . '/config.php';
        $pdo = new PDO(
            "mysql:host={$c['db_host']};dbname={$c['db_name']}",
            $c['db_user'],
            $c['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

function client(): \SendAfrica\Client {
    static $client = null;
    if ($client === null) {
        $client = new \SendAfrica\Client(require __DIR__ . '/config.php')['sendafrica_api_key']);
    }
    return $client;
}
```

---

## Database Setup

Run this SQL to create the users table with OTP support:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255),
    phone_verified TINYINT(1) DEFAULT 0,

    -- Registration OTP
    reg_otp_hash VARCHAR(255),
    reg_otp_created_at DATETIME,

    -- Login OTP
    login_otp_hash VARCHAR(255),
    login_otp_created_at DATETIME,

    -- Password reset OTP
    reset_otp_hash VARCHAR(255),
    reset_otp_created_at DATETIME,

    -- Phone verification OTP
    phone_otp_hash VARCHAR(255),
    phone_otp_created_at DATETIME,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## User Registration with Phone Verification

When a new user signs up, verify their phone number before creating the account.

### send-registration-otp.php

Called when the user submits the registration form.

```php
<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$phone = trim($_POST['phone'] ?? '');
$name  = trim($_POST['name'] ?? '');

if (empty($phone) || empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and phone number are required.']);
    exit();
}

// Check if phone is already registered
$exists = db()->prepare("SELECT id FROM users WHERE phone = ?");
$exists->execute([$phone]);
if ($exists->fetch()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'This phone number is already registered.']);
    exit();
}

// Send OTP
$otp = client()->auth->sendRegistrationOtp($phone);

// Save hashed OTP to database
$hash = client()->auth->hash($otp['otp']);
$stmt = db()->prepare("INSERT INTO users (phone, name, reg_otp_hash, reg_otp_created_at) VALUES (?, ?, ?, NOW())");
$stmt->execute([$phone, $name, $hash]);

echo json_encode(['success' => true, 'message' => 'Verification code sent to your phone.']);
```

### verify-registration.php

Called when the user enters the code they received.

```php
<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$phone = trim($_POST['phone'] ?? '');
$code  = trim($_POST['code'] ?? '');

if (empty($phone) || empty($code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone and code are required.']);
    exit();
}

// Get user from database
$stmt = db()->prepare("SELECT id, reg_otp_hash, reg_otp_created_at FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['reg_otp_hash']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No pending verification. Please register again.']);
    exit();
}

// Verify the code
$result = client()->auth->verifyWithExpiry(
    entered: $code,
    expected: $user['reg_otp_hash'],
    createdAt: $user['reg_otp_created_at'],
    expiryMinutes: 10
);

if ($result['valid']) {
    // Mark account as verified, clear OTP
    db()->prepare("UPDATE users SET phone_verified = 1, reg_otp_hash = NULL, reg_otp_created_at = NULL WHERE id = ?")
        ->execute([$user['id']]);

    // Start session
    session_start();
    $_SESSION['user_id'] = $user['id'];

    echo json_encode(['success' => true, 'message' => 'Account created successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
```

### The HTML form

```html
<!-- register.html -->
<form id="registerForm">
    <input type="text" name="name" placeholder="Full name" required>
    <input type="text" name="phone" placeholder="0712345678" required>
    <button type="submit">Register</button>
</form>

<div id="otpSection" style="display:none;">
    <p>Enter the code sent to your phone:</p>
    <input type="text" id="otpCode" placeholder="000000" maxlength="6" required>
    <button onclick="verifyOtp()">Verify</button>
</div>

<script>
document.getElementById('registerForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = new FormData(e.target);

    const res = await fetch('send-registration-otp.php', { method: 'POST', body: data });
    const json = await res.json();

    if (json.success) {
        document.getElementById('otpSection').style.display = 'block';
    } else {
        alert(json.message);
    }
};

async function verifyOtp() {
    const phone = document.querySelector('[name=phone]').value;
    const code = document.getElementById('otpCode').value;

    const res = await fetch('verify-registration.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `phone=${encodeURIComponent(phone)}&code=${encodeURIComponent(code)}`
    });
    const json = await res.json();

    if (json.success) {
        window.location.href = '/dashboard.php';
    } else {
        alert(json.message);
    }
}
</script>
```

---

## Login with Two-Factor Authentication

After the user enters their password, send a login code to their phone.

### login.php

```php
<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$phone    = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($phone) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone and password are required.']);
    exit();
}

// Find user
$stmt = db()->prepare("SELECT id, password_hash, phone_verified FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    exit();
}

if (!$user['phone_verified']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Please verify your phone number first.']);
    exit();
}

// Password correct — send login OTP
$otp = client()->auth->sendLoginOtp($phone, appName: 'MyApp');

$hash = client()->auth->hash($otp['otp']);
db()->prepare("UPDATE users SET login_otp_hash = ?, login_otp_created_at = NOW() WHERE id = ?")
    ->execute([$hash, $user['id']]);

echo json_encode(['success' => true, 'message' => 'Login code sent to your phone.']);
```

### verify-login.php

```php
<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$phone = trim($_POST['phone'] ?? '');
$code  = trim($_POST['code'] ?? '');

$stmt = db()->prepare("SELECT id, login_otp_hash, login_otp_created_at FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['login_otp_hash']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No pending login. Please try again.']);
    exit();
}

$result = client()->auth->verifyWithExpiry($code, $user['login_otp_hash'], $user['login_otp_created_at']);

if ($result['valid']) {
    // Clear OTP, create session
    db()->prepare("UPDATE users SET login_otp_hash = NULL, login_otp_created_at = NULL WHERE id = ?")
        ->execute([$user['id']]);

    session_start();
    $_SESSION['user_id'] = $user['id'];

    echo json_encode(['success' => true, 'message' => 'Welcome back!']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
```

### The HTML form

```html
<!-- login.html -->
<form id="loginForm">
    <input type="text" name="phone" placeholder="0712345678" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
</form>

<div id="otpSection" style="display:none;">
    <p>Enter the code sent to your phone:</p>
    <input type="text" id="otpCode" placeholder="000000" maxlength="6" required>
    <button onclick="verifyLogin()">Verify</button>
</div>

<script>
document.getElementById('loginForm').onsubmit = async (e) => {
    e.preventDefault();
    const data = new FormData(e.target);

    const res = await fetch('login.php', { method: 'POST', body: data });
    const json = await res.json();

    if (json.success) {
        document.getElementById('otpSection').style.display = 'block';
    } else {
        alert(json.message);
    }
};

async function verifyLogin() {
    const phone = document.querySelector('[name=phone]').value;
    const code = document.getElementById('otpCode').value;

    const res = await fetch('verify-login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `phone=${encodeURIComponent(phone)}&code=${encodeURIComponent(code)}`
    });
    const json = await res.json();

    if (json.success) {
        window.location.href = '/dashboard.php';
    } else {
        alert(json.message);
    }
}
</script>
```

---

## Forgot Password / Reset Password

### forgot-password.php

```php
<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$phone = trim($_POST['phone'] ?? '');

$stmt = db()->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Don't reveal whether the phone exists
    echo json_encode(['success' => true, 'message' => 'If that number is registered, a reset code has been sent.']);
    exit();
}

$otp = client()->auth->sendPasswordResetOtp($phone);
$hash = client()->auth->hash($otp['otp']);

db()->prepare("UPDATE users SET reset_otp_hash = ?, reset_otp_created_at = NOW() WHERE id = ?")
    ->execute([$hash, $user['id']]);

echo json_encode(['success' => true, 'message' => 'Reset code sent to your phone.']);
```

### reset-password.php

```php
<?php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$phone       = trim($_POST['phone'] ?? '');
$code        = trim($_POST['code'] ?? '');
$newPassword = $_POST['new_password'] ?? '';

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
    exit();
}

$stmt = db()->prepare("SELECT id, reset_otp_hash, reset_otp_created_at FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['reset_otp_hash']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No pending reset. Please request a new code.']);
    exit();
}

$result = client()->auth->verifyWithExpiry($code, $user['reset_otp_hash'], $user['reset_otp_created_at']);

if ($result['valid']) {
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->prepare("UPDATE users SET password_hash = ?, reset_otp_hash = NULL, reset_otp_created_at = NULL WHERE id = ?")
        ->execute([$newHash, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
```

---

## Phone Number Verification

For verifying a phone number separately (not during registration):

```php
<?php
// send-phone-otp.php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$phone = trim($_POST['phone'] ?? '');
$userId = $_SESSION['user_id'];

$otp = client()->auth->sendPhoneVerificationOtp($phone, length: 4);
$hash = client()->auth->hash($otp['otp']);

db()->prepare("UPDATE users SET phone_otp_hash = ?, phone_otp_created_at = NOW() WHERE id = ?")
    ->execute([$hash, $userId]);

echo json_encode(['success' => true, 'message' => 'Verification code sent.']);
```

```php
<?php
// verify-phone.php
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

$code = trim($_POST['code'] ?? '');
$userId = $_SESSION['user_id'];

$stmt = db()->prepare("SELECT phone_otp_hash, phone_otp_created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$result = client()->auth->verifyWithExpiry($code, $user['phone_otp_hash'], $user['phone_otp_created_at']);

if ($result['valid']) {
    db()->prepare("UPDATE users SET phone_verified = 1, phone_otp_hash = NULL, phone_otp_created_at = NULL WHERE id = ?")
        ->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Phone verified!']);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
```

---

## Sending Transactional SMS

Beyond auth, send any transactional message:

```php
// Order confirmation
$client->send('0712345678', 'Your order #1234 has been confirmed. Track at myshop.co.tz/track/1234');

// Delivery notification
$client->send('0712345678', 'Your order is out for delivery. Expected arrival: 3:00 PM.');

// Appointment reminder
$client->send('0712345678', 'Reminder: You have an appointment tomorrow at 10:00 AM.');

// Payment confirmation
$client->send('0712345678', 'Payment of TZS 50,000 received for order #1234. Thank you!');
```

---

## Error Handling

Wrap all SDK calls in try/catch. The SDK throws typed exceptions for each error scenario:

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
    $client->send('0712345678', 'Your message');
} catch (AuthenticationException $e) {
    // API key is invalid or revoked
    error_log('SendAfrica: Check your API key in Settings > API Keys');
} catch (InsufficientCreditsException $e) {
    // Not enough credits
    error_log('SendAfrica: Buy more credits at app.sendafrica.online');
} catch (RateLimitException $e) {
    // Too many requests — wait and retry
    sleep(60);
    $client->send('0712345678', 'Your message');
} catch (ValidationException $e) {
    // Bad phone number, missing fields, etc.
    error_log('SendAfrica: ' . $e->getMessage());
} catch (SendAfricaException $e) {
    // Any other error
    error_log('SendAfrica [' . $e->getErrorCode() . ']: ' . $e->getMessage());
}
```

---

## API Reference

### Auth Service

| Method | Description |
|--------|-------------|
| `sendRegistrationOtp($phone)` | Send OTP for new user signup |
| `sendLoginOtp($phone, $appName)` | Send OTP for two-factor login |
| `sendPasswordResetOtp($phone)` | Send OTP for password reset |
| `sendPhoneVerificationOtp($phone)` | Send OTP to verify a phone number |
| `sendTransactionOtp($phone, $details)` | Send OTP for payment/order confirmation |
| `sendCustomOtp($phone, $message)` | Send OTP with your own message (use `{{code}}` placeholder) |
| `verify($entered, $expected)` | Simple OTP check |
| `verifyWithExpiry($entered, $hash, $createdAt)` | OTP check with expiry validation |
| `hash($otp)` | Hash an OTP for database storage |
| `verifyHash($entered, $hash)` | Verify input against a stored hash |
| `generate($length)` | Generate an OTP without sending |
| `isExpired($createdAt, $expiryMinutes)` | Check if an OTP has expired |

### SMS Service

| Method | Description |
|--------|-------------|
| `send($to, $message, $from)` | Send a single SMS |
| `sendBulk($to, $message, $from)` | Send to multiple recipients (max 100) |

### Credits Service

| Method | Description |
|--------|-------------|
| `getBalance()` | Get available credit count |
| `balance()` | Get full balance details |
| `history($page, $perPage)` | Get credit transaction history |
| `packages()` | List available credit packages |

### All auth methods default to 10-minute expiry

```php
$otp = $client->auth->sendRegistrationOtp($phone);
// OTP expires in 10 minutes

$result = $client->auth->verifyWithExpiry($code, $hash, $createdAt, expiryMinutes: 10);
```

---

## Phone Number Format

| Format | Example |
|--------|---------|
| Local | `0712345678` |
| International | `+255712345678` |
| Without + | `255712345678` |

---

## Help & Support

- [SendAfrica API Documentation](https://docs.sendafrica.online)
- [SendAfrica Dashboard](https://app.sendafrica.online)
- WhatsApp: +255 692 069 230

---

## License

MIT

---

Want the fully working sample code? Grab it here: https://github.com/mohamed-sinani/sendafrica-test-sdk
