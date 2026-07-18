<?php
/**
 * Create a credit top-up and check pricing.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;

$client = new SendAfrica();

// Check pricing
$rate = $client->payments->rate();
echo "Minimum top-up: {$rate->minAmountTzs} TZS\n";
foreach ($rate->tiers as $tier) {
    echo "  Up to {$tier->maxAmountTzs} TZS: {$tier->rateTzsPerCredit} TZS/credit\n";
}

echo "\n";

// Create a manual top-up
$payment = $client->payments->create(amount: 50000);
echo "Payment: {$payment->id}\n";
echo "Status: {$payment->status}\n";
echo "Credits: {$payment->creditAmount}\n";
