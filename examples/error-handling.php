<?php
/**
 * Stripe-like error handling with specific exception types.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;
use SendAfrica\Exceptions\{
    SendAfricaException,
    AuthenticationException,
    InsufficientCreditsException,
    RateLimitException,
    InvalidPhoneException,
    ValidationException
};

$client = new SendAfrica();

try {
    $client->sms->send(to: '0712345678', message: 'Hello');
} catch (InvalidPhoneException $e) {
    echo "Bad phone number: " . $e->getMessage() . "\n";
} catch (InsufficientCreditsException $e) {
    echo "Not enough credits -- top up first\n";
} catch (RateLimitException $e) {
    echo "Rate limited -- retry after {$e->getRetryAfter()}s\n";
} catch (AuthenticationException $e) {
    echo "Check your API key\n";
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
} catch (SendAfricaException $e) {
    echo "API error: " . $e->getMessage() . "\n";
    echo "Status: " . $e->getStatusCode() . "\n";
    echo "Request ID: " . $e->getRequestId() . "\n";
}
