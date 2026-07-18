# SendAfrica PHP SDK

[![PHP](https://img.shields.io/packagist/php-v/sendafrica/php-sdk)](https://packagist.org/packages/sendafrica/php-sdk)
[![Version](https://img.shields.io/packagist/v/sendafrica/php-sdk)](https://packagist.org/packages/sendafrica/php-sdk)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Official PHP client for the [SendAfrica](https://sendafrica.online) SMS
Infrastructure-as-a-Service API. Designed to feel like Stripe's PHP library:
simple for a first integration, enough control for production use.

---

## What You Get

- **Fail-fast validation** -- phone numbers are normalized to E.164 and
  validated locally *before* any network call. Bad input never hits the wire.
- **Automatic retries** -- exponential backoff on 429/5xx/connection errors,
  with `Retry-After` header respect. No retry loops in your code.
- **Phone number normalization** -- pass `0712345678`, `+255 712 345 678`, or
  `255712345678` and it all works. The SDK handles the conversion.
- **SMS cost estimation** -- `$client->sms->analyze()` tells you encoding
  (GSM-7 vs UCS-2), segment count, and credit cost with zero network calls.
- **Full auth system** -- `$client->auth` gives you registration OTP,
  login OTP, password reset, phone verification, transaction OTP, and
  custom OTP flows with built-in hashing and expiry verification.
- **Stripe-like error hierarchy** -- every error inherits from
  `SendAfricaException`. Catch the base class or get specific with
  `InsufficientCreditsException`, `RateLimitException`, etc.
- **Bulk SMS with partial failure handling** -- `sendMany()` sends
  sequentially, collects per-message failures, and never aborts the batch
  on a single bad number.
- **Webhook signature verification** -- HMAC-SHA256 verification for
  incoming webhook payloads.
- **CLI** -- quick balance checks and SMS sending from the terminal without
  writing any code.

---

## Install

```bash
composer require sendafrica/php-sdk
```

**Requirements:** PHP 7.4+, ext-curl, ext-json

---

## Quickstart

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use SendAfrica\SendAfrica;

$client = new SendAfrica(apiKey: 'SA-xxxxx');

$result = $client->sms->send(to: '0712345678', message: 'Welcome to SendAfrica');
echo $result->messageId . "\n";
echo $result->status . "\n";
echo $result->creditsUsed . "\n";
```

The API key can also be set via the `SENDAFRICA_API_KEY` environment
variable, in which case `new SendAfrica()` needs no arguments:

```bash
export SENDAFRICA_API_KEY="SA-xxxxx"
```

---

## Authentication

The SDK resolves your API key in this order:

1. Explicit argument: `new SendAfrica(apiKey: 'SA-xxxxx')`
2. Environment variable: `SENDAFRICA_API_KEY`
3. If neither is set, `AuthenticationException` is raised

Every request includes an `Authorization: Bearer <key>` header. The SDK also
attaches a `User-Agent: sendafrica-php/1.0` header and a unique
`X-Request-Id` hex string per request for tracing.

---

## Configuration

```php
$client = new SendAfrica(
    apiKey: 'SA-xxxxx',
    baseUrl: 'https://api.sendafrica.online/v1',
    timeout: 10,
    maxRetries: 3,
    environment: 'production',
    debug: false,
    webhookSecret: null,
);
```

| Parameter | Default | Description |
|---|---|---|
| `apiKey` | `null` | API key, or falls back to `SENDAFRICA_API_KEY` env var |
| `baseUrl` | `"https://api.sendafrica.online/v1"` | API base URL |
| `timeout` | `10` | Request timeout in seconds |
| `maxRetries` | `3` | Max retry attempts on 429/5xx/connection errors |
| `environment` | `"production"` | Label (read-only, for logging/display) |
| `debug` | `false` | Print request/response logs to stderr |
| `webhookSecret` | `null` | HMAC-SHA256 secret for webhook verification |

---

## Resources

| Resource | Methods |
|---|---|
| `$client->sms` | `send`, `sendMany`, `analyze` |
| `$client->auth` | `sendRegistrationOtp`, `sendLoginOtp`, `sendPasswordResetOtp`, `sendPhoneVerificationOtp`, `sendTransactionOtp`, `sendCustomOtp`, `verify`, `verifyWithExpiry`, `generate`, `hash`, `verifyHash`, `isExpired` |
| `$client->credits` | `balance`, `history` |
| `$client->payments` | `create`, `rate` |
| `$client->webhooks` | `parse` |

### SMS

#### `$client->sms->send(to, message, sender: null)`

Send a single SMS. Phone numbers are normalized locally to E.164 before
any network call.

```php
$result = $client->sms->send(
    to: '0712345678',
    message: 'Your OTP is 123456',
    sender: 'MyBrand',  // optional, max 11 chars
);

echo $result->messageId;    // "SA-abc123..."
echo $result->status;        // "queued"
echo $result->creditsUsed;  // 1
```

**Returns:** `SmsResult` object

| Property | Type | Description |
|---|---|---|
| `messageId` | `string` | Server-assigned unique ID |
| `status` | `string` | Delivery status (e.g. `"queued"`) |
| `creditsUsed` | `int` | Credits consumed |
| `cost` | `?string` | Cost string if provided |
| `to` | `?string` | Recipient phone number |

**Throws:** `InvalidPhoneException`, `ValidationException`, `InsufficientCreditsException`

#### `$client->sms->sendMany(messages, sender: null, rateLimitPerSec: 10.0)`

Send multiple SMS. Each failure is collected individually -- the batch
does not abort on a single bad number.

```php
$results = $client->sms->sendMany(
    messages: [
        ['to' => '0711111111', 'message' => 'Hello John'],
        ['to' => '0722222222', 'message' => 'Hello Mary'],
        ['to' => '+255733333333', 'message' => 'Hello Alex'],
    ],
    sender: 'MyBrand'
);

echo $results->getSentCount();   // 2
echo $results->getFailedCount(); // 1

foreach ($results->failed as $failure) {
    echo $failure['index'] . ' ' . $failure['to'] . ': ' . $failure['error'] . "\n";
}
```

Each item in `messages` is an associative array with:

| Key | Required | Description |
|---|---|---|
| `'to'` | yes | Phone number (any format) |
| `'message'` | yes | SMS body text |
| `'sender'` | no | Overrides the default `sender` for this message |

**Returns:** `BulkSmsResult` object

| Property / Method | Type | Description |
|---|---|---|
| `results` | `SmsResult[]` | Successfully sent messages |
| `failed` | `array[]` | `['index' => int, 'to' => string, 'error' => string]` |
| `getSentCount()` | `int` | Number of successfully sent messages |
| `getFailedCount()` | `int` | Number of failed messages |

The `rateLimitPerSec` parameter paces requests with a client-side
delay (`1.0 / rateLimitPerSec` seconds between sends). Default is
10 requests per second.

#### `$client->sms->analyze(message)`

Preview encoding, segment count, and credit cost **without any network
call**. Useful for showing users the cost before they confirm sending.

```php
$analysis = $client->sms->analyze('Habari, how are you?');
echo $analysis->encoding;   // "GSM-7"
echo $analysis->characters; // 22
echo $analysis->parts;      // 1
echo $analysis->credits;    // 1

$emoji = $client->sms->analyze("Habari \xF0\x9F\x98\x8A");
echo $emoji->encoding;   // "UCS-2"
echo $emoji->characters; // 8
echo $emoji->parts;      // 1
echo $emoji->credits;    // 1
```

**Returns:** `SmsAnalysis` object

| Property | Type | Description |
|---|---|---|
| `encoding` | `string` | `"GSM-7"` or `"UCS-2"` |
| `characters` | `int` | Character count |
| `parts` | `int` | Number of SMS segments |
| `credits` | `int` | Estimated credits (1 per segment) |

Segmentation rules:
- **GSM-7** (basic Latin + limited symbols): 160 chars/single, 153 when
  concatenated
- **UCS-2** (emoji, accented characters outside GSM-7): 70 chars/single,
  67 when concatenated

> **Note:** `credits` is an estimate for UI display. The authoritative
> number is `creditsUsed` on the `SmsResult` from `$client->sms->send()`.

### Auth

The `$client->auth` resource provides a complete SMS-based auth system out
of the box. OTP generation, message templates, hashing, and expiry
verification -- all built in.

#### `$client->auth->sendRegistrationOtp(to, length: 6, expiry: 10, sender: null)`

Send OTP for new user signup.

```php
$otp = $client->auth->sendRegistrationOtp(to: '0712345678');

echo $otp['otp'];         // "482916" -- the code sent
echo $otp['message_id'];  // server-assigned ID
echo $otp['credits_used']; // 1
echo $otp['expires_in'];  // 600 (seconds)

// In your app: hash and store in your database
$hash = $client->auth->hash($otp['otp']);
// INSERT INTO users (phone, otp_hash, otp_created_at) VALUES (?, ?, NOW())
```

**Returns:** `array{otp: string, message_id: string, status: string, credits_used: int, expires_in: int}`

#### `$client->auth->sendLoginOtp(to, appName: '', length: 6, expiry: 5, sender: null)`

Send OTP for two-factor login.

```php
$otp = $client->auth->sendLoginOtp(to: '0712345678', appName: 'MyApp');
// Message: "Your MyApp login code is 482916. Valid for 5 minutes..."
```

#### `$client->auth->sendPasswordResetOtp(to, length: 6, expiry: 10, sender: null)`

Send OTP for password reset.

```php
$otp = $client->auth->sendPasswordResetOtp(to: '0712345678');
// Message: "Your password reset code is 482916. Valid for 10 minutes..."
```

#### `$client->auth->sendPhoneVerificationOtp(to, length: 4, expiry: 10, sender: null)`

Send OTP to verify a phone number. Default length is 4 digits.

```php
$otp = $client->auth->sendPhoneVerificationOtp(to: '0712345678', length: 4);
```

#### `$client->auth->sendTransactionOtp(to, details: '', length: 6, expiry: 5, sender: null)`

Send OTP for order/payment confirmation.

```php
$otp = $client->auth->sendTransactionOtp(to: '0712345678', details: 'order #1234');
// Message: "Confirm order #1234 with code 482916..."
```

#### `$client->auth->sendCustomOtp(to, message, length: 6, expiry: 10, sender: null)`

Send OTP with your own message template. Use `{{code}}` as placeholder.

```php
$otp = $client->auth->sendCustomOtp(
    to: '0712345678',
    message: 'Use {{code}} to verify your account on MyApp. Expires in 10 min.'
);
```

#### `$client->auth->verify(entered, expected)`

Simple OTP check. Returns `true` if codes match.

```php
if ($client->auth->verify($userInput, $otpCode)) {
    // correct
}
```

#### `$client->auth->verifyWithExpiry(entered, expectedHash, createdAt, expiryMinutes: 10)`

Verify an OTP with expiry check. Use this for production.

```php
$result = $client->auth->verifyWithExpiry(
    entered: $userInput,
    expectedHash: $storedHash,  // from password_hash()
    createdAt: '2026-07-18T12:00:00',
    expiryMinutes: 10
);

if ($result['valid']) {
    // OTP correct and not expired
} else {
    echo $result['message']; // "OTP has expired" or "Invalid code"
}
```

**Returns:** `array{valid: bool, expired: bool, message: string}`

#### `$client->auth->generate(length: 6)`

Generate a numeric OTP without sending it.

#### `$client->auth->hash(otp)`

Hash an OTP for secure database storage (uses `password_hash()`).

#### `$client->auth->verifyHash(entered, hash)`

Verify user input against a stored hash (uses `password_verify()`).

#### `$client->auth->isExpired(createdAt, expiryMinutes: 10)`

Check if an OTP has expired without verifying it.

### Credits

#### `$client->credits->balance()`

```php
$balance = $client->credits->balance();
echo $balance->accountId;  // "acc_abc123"
echo $balance->balance;     // 4820
```

**Returns:** `CreditBalance` object

| Property | Type | Description |
|---|---|---|
| `accountId` | `string` | Account identifier |
| `balance` | `int` | Current credit balance |

#### `$client->credits->history(page: 1, perPage: 25)`

List credit transactions with page-based pagination.

```php
$transactions = $client->credits->history(page: 1, perPage: 50);

foreach ($transactions as $tx) {
    echo $tx->id . ' ' . $tx->type . ' ' . $tx->amount . ' ' . $tx->balanceAfter . ' ' . $tx->createdAt . "\n";
}
```

**Parameters:**

| Parameter | Default | Description |
|---|---|---|
| `page` | `1` | Page number (1-indexed) |
| `perPage` | `25` | Items per page (max 200) |

**Returns:** `CreditTransaction[]`

| Property | Type | Description |
|---|---|---|
| `id` | `string` | Transaction ID |
| `type` | `string` | `"debit"` or `"credit"` |
| `amount` | `int` | Amount in credits |
| `balanceAfter` | `int` | Balance after this transaction |
| `description` | `?string` | Human-readable description |
| `createdAt` | `?string` | Timestamp |

### Payments

Credit top-ups are pay-as-you-go: you specify any TZS amount (above
the minimum) and the API converts it to credits at the current tiered
rate.

#### `$client->payments->create(amount, provider: 'manual', phone: null)`

```php
// Manual top-up
$payment = $client->payments->create(amount: 50000);
echo $payment->id . ' ' . $payment->status . ' ' . $payment->creditAmount . "\n";

// Mobile money top-up (phone is required)
$payment = $client->payments->create(
    amount: 50000,
    provider: 'snippe',
    phone: '0712345678',
);
```

**Parameters:**

| Parameter | Required | Default | Description |
|---|---|---|---|
| `amount` | yes | -- | Top-up amount in TZS (must be positive) |
| `provider` | no | `"manual"` | Payment provider (`"manual"`, `"snippe"`, etc.) |
| `phone` | no | `null` | Required for mobile-money providers, ignored for `"manual"` |

**Returns:** `Payment` object

| Property | Type | Description |
|---|---|---|
| `id` | `string` | Payment/order ID |
| `status` | `string` | Payment status |
| `amount` | `?int` | Amount in TZS |
| `creditAmount` | `?int` | Credits to be credited |
| `currency` | `string` | `"TZS"` |
| `provider` | `?string` | Payment provider used |
| `source` | `?string` | Payment source |
| `createdAt` | `?string` | Timestamp |

#### `$client->payments->rate()`

Fetch the current pricing schedule: minimum top-up amount and the
tiered TZS-per-credit rate table.

```php
$rate = $client->payments->rate();
echo "Minimum top-up: {$rate->minAmountTzs} TZS\n";

foreach ($rate->tiers as $tier) {
    echo "  Up to {$tier->maxAmountTzs} TZS: {$tier->rateTzsPerCredit} TZS/credit\n";
}
```

**Returns:** `VoucherRate` object

| Property | Type | Description |
|---|---|---|
| `minAmountTzs` | `int` | Minimum top-up amount in TZS |
| `tiers` | `RateTier[]` | Pricing tiers |

Where each `RateTier` is:

| Property | Type | Description |
|---|---|---|
| `maxAmountTzs` | `int` | Upper bound (0 = unbounded/top tier) |
| `rateTzsPerCredit` | `int` | Price per credit in TZS |

Use this to validate an amount client-side before calling `create()`:

```php
$rate = $client->payments->rate();
$amountTzs = 30000;

if ($amountTzs < $rate->minAmountTzs) {
    echo "Minimum is {$rate->minAmountTzs} TZS\n";
}
```

### Webhooks

> **Status:** speculative. The SendAfrica API does not yet forward signed
> events to customer endpoints. This resource is ready for when that
> ships server-side.

#### `$client->webhooks->parse(payload, signature: null, secret: null)`

Parse and optionally verify an incoming webhook payload using
HMAC-SHA256.

```php
$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SENDAFRICA_SIGNATURE'] ?? null;

$event = $client->webhooks->parse(payload: $rawBody, signature: $signature);

if ($event->type === 'sms.delivered') {
    echo "Message {$event->messageId} delivered\n";
}
```

**Parameters:**

| Parameter | Required | Description |
|---|---|---|
| `payload` | yes | Raw webhook body (`string` or `resource`) |
| `signature` | no | Value from `X-SendAfrica-Signature` header |
| `secret` | no | Overrides the client-level `webhookSecret` |

**Returns:** `WebhookEvent` object

| Property | Type | Description |
|---|---|---|
| `type` | `string` | Event type (e.g. `"sms.delivered"`) |
| `messageId` | `?string` | Associated message ID |
| `data` | `array` | Full raw event data |

**Throws:** `WebhookSignatureException` on signature mismatch

---

## Phone Number Handling

The SDK normalizes phone numbers to E.164 format locally, before any
network call. These formats all work:

| Input | Output |
|---|---|
| `0712345678` | `+255712345678` |
| `712345678` | `+255712345678` |
| `255712345678` | `+255712345678` |
| `+255712345678` | `+255712345678` |
| `+255 712 345 678` | `+255712345678` |

The default country code is `255` (Tanzania). Numbers that cannot be
confidently normalized throw `InvalidPhoneException` without hitting the API.

The SDK is deliberately permissive beyond Tanzania: any digit string of
plausible length (9-15 digits) that already has a country code will
normalize fine. The API's own validator is the source of truth -- the
local check exists to catch typos early.

---

## Error Handling

All errors inherit from `SendAfricaException`. Catch the base class for
generic handling, or catch specific subtypes for finer-grained control:

```php
<?php

use SendAfrica\SendAfrica;
use SendAfrica\Exceptions\{
    SendAfricaException,
    InsufficientCreditsException,
    RateLimitException,
    InvalidPhoneException
};

$client = new SendAfrica();

try {
    $client->sms->send(to: '0712345678', message: 'Hello');
} catch (InvalidPhoneException $e) {
    echo "Bad phone number: " . $e->getMessage() . "\n";
} catch (InsufficientCreditsException $e) {
    echo "Not enough credits -- top up first\n";
} catch (RateLimitException $e) {
    echo "Rate limited -- retry after " . $e->getRetryAfter() . "s\n";
} catch (SendAfricaException $e) {
    echo "API error: " . $e->getMessage() . "\n";
    echo "Status: " . $e->getStatusCode() . "\n";
    echo "Request ID: " . $e->getRequestId() . "\n";
}
```

### Exception hierarchy

```
SendAfricaException (base)
├── AuthenticationException        (HTTP 401)
├── ValidationException            (HTTP 400, 422)
│   └── InvalidPhoneException      (local or server-side phone validation)
├── InsufficientCreditsException   (HTTP 402)
├── RateLimitException             (HTTP 429, has getRetryAfter())
├── NotFoundException              (HTTP 404)
├── ServerException                (HTTP 5xx)
├── ConnectionException            (network/timeout)
└── WebhookSignatureException      (HMAC mismatch)
```

### Properties on every exception

| Property | Type | Description |
|---|---|---|
| `getMessage()` | `string` | Human-readable error message |
| `getStatusCode()` | `?int` | HTTP status code (if applicable) |
| `getRequestId()` | `?string` | Request ID for tracing |
| `getErrorCode()` | `string` | Machine-readable error code |
| `getResponseBody()` | `?string` | Raw response payload |

`RateLimitException` also has `getRetryAfter(): ?float` (seconds from
the `Retry-After` header).

### Retry behavior

The SDK automatically retries on:

| Status | Behavior |
|---|---|
| `429` | Respects `Retry-After` header, then exponential backoff |
| `500`, `502`, `503`, `504` | Exponential backoff |
| Connection errors | No retry (fail immediately) |

Backoff formula: `min(0.5 * 2^(attempt-1), 8.0)` seconds. Default max
retries: 3. Total max wait per request: ~15 seconds.

---

## CLI

The SDK installs a `sendafrica` command for quick operations:

```bash
# Set your API key
export SENDAFRICA_API_KEY="SA-xxxxx"

# Check your credit balance
sendafrica balance
# Credits: 4820

# Send a single SMS
sendafrica sms send --to 0712345678 --message "Hello from SendAfrica"
# Sent: SA-abc123 (status=queued, credits=1)

# Send with a sender ID
sendafrica sms send --to 0712345678 --message "OTP: 4829" --sender MyBrand
```

---

## Lessons: Using the SDK Effectively

These are practical patterns for getting the most out of the SDK.

### Lesson 1: Check balance before sending

Avoid `InsufficientCreditsException` by checking first:

```php
$balance = $client->credits->balance();
if ($balance->balance < 10) {
    echo "Low balance: {$balance->balance} credits remaining\n";
    // prompt user to top up
}
```

### Lesson 2: Preview cost with analyze()

`analyze()` makes zero network calls -- it's pure local computation.
Use it to show users the cost before they confirm:

```php
$analysis = $client->sms->analyze($message);
$cost = $analysis->credits;
// Show "This message will cost {$cost} credit(s)" in your UI
```

### Lesson 3: Handle partial failures in bulk sends

`sendMany()` never aborts on a single failure. Always check
`getFailedCount()`:

```php
$results = $client->sms->sendMany($messages);
if ($results->getFailedCount() > 0) {
    foreach ($results->failed as $f) {
        echo "Message to {$f['to']} failed: {$f['error']}\n";
    }
}
```

### Lesson 4: Phone numbers just work

Don't preprocess phone numbers in your code. Pass whatever format
you have:

```php
// All of these work:
$client->sms->send(to: '0712345678', message: 'Hello');
$client->sms->send(to: '+255712345678', message: 'Hello');
$client->sms->send(to: '255712345678', message: 'Hello');
$client->sms->send(to: '+255 712 345 678', message: 'Hello');
```

### Lesson 5: Catch specific errors

Don't catch `SendAfricaException` for everything -- use specific exceptions
for better UX:

```php
try {
    $client->sms->send(to: '0712345678', message: 'Hello');
} catch (InvalidPhoneException $e) {
    // Show "Please check the phone number"
} catch (InsufficientCreditsException $e) {
    // Show "Please top up your credits"
} catch (RateLimitException $e) {
    // Show "Please wait a moment and try again"
}
```

### Lesson 6: Use the CLI for quick tests

Verify your API key works without writing any code:

```bash
export SENDAFRICA_API_KEY="SA-xxxxx"
sendafrica balance
```

If that returns your balance, your setup is correct.

### Lesson 7: Retry is built in

Don't implement your own retry logic. The SDK handles 429 and 5xx
automatically with exponential backoff:

```php
// This is safe -- the SDK retries transient failures internally
$result = $client->sms->send(to: '0712345678', message: 'Hello');
```

### Lesson 8: Webhooks are ready but not live

You can write your webhook handler now, but don't depend on receiving
events yet:

```php
// This code is ready -- it just won't receive events until
// SendAfrica ships outbound webhooks server-side
$event = $client->webhooks->parse($payload, signature: $signature);
```

---

## Project Layout

```
src/
├── SendAfrica.php              # Main client class
├── HttpClient.php              # HTTP transport with retries
├── Exceptions/
│   ├── SendAfricaException.php         # Base exception
│   ├── AuthenticationException.php     # HTTP 401
│   ├── ValidationException.php         # HTTP 400
│   ├── InvalidPhoneException.php       # Local phone validation
│   ├── InsufficientCreditsException.php # HTTP 402
│   ├── RateLimitException.php          # HTTP 429
│   ├── NotFoundException.php           # HTTP 404
│   ├── ServerException.php             # HTTP 5xx
│   ├── ConnectionException.php         # Network errors
│   └── WebhookSignatureException.php   # HMAC mismatch
├── Resources/
│   ├── SmsResource.php          # send, sendMany, analyze
│   ├── AuthResource.php         # OTP generation, sending, hashing, verification
│   ├── CreditsResource.php      # balance, history
│   ├── PaymentsResource.php     # create, rate
│   └── WebhooksResource.php     # parse
├── Models/
│   ├── SmsResult.php
│   ├── BulkSmsResult.php
│   ├── SmsAnalysis.php
│   ├── CreditBalance.php
│   ├── CreditTransaction.php
│   ├── Payment.php
│   ├── VoucherRate.php
│   ├── RateTier.php
│   └── WebhookEvent.php
└── Utils/
    ├── PhoneNormalizer.php      # E.164 normalization
    └── SmsAnalyzer.php          # GSM-7/UCS-2 encoding + segment analysis
```

---

## Roadmap

- **Phase 1 (done):** Client, auth, SMS send/bulk/analyze, credits
  balance/history, payments create/rate, error hierarchy, response
  models, CLI, phone normalization, retry/backoff
- **Phase 2:** Async support (ext-parallel/ext-swoole), bulk SMS via
  server-side endpoint, CLI expansion (history, payments, sendMany)
- **Phase 3:** Campaigns, contacts, templates, scheduling

---

## Contributing

Run tests:

```bash
composer install
vendor/bin/phpunit
```

---

## License

MIT
