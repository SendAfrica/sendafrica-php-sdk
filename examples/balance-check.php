<?php
/**
 * SendAfrica PHP SDK — Balance & Credits Example
 *
 * Check your balance, view credit history, and list packages.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\Client;

$client = new Client('YOUR_API_KEY_HERE');

// Quick balance check
$credits = $client->getBalance();
echo "SMS Credits: {$credits}\n\n";

// Full balance details
$balance = $client->credits->balance();
echo "Account ID: {$balance['account_id']}\n";
echo "Balance: {$balance['balance']} credits\n\n";

// Credit history (last 5 transactions)
$history = $client->credits->history(page: 1, perPage: 5);
echo "Recent transactions:\n";
foreach ($history['items'] as $tx) {
    $sign = $tx['amount'] > 0 ? '+' : '';
    echo "  {$sign}{$tx['amount']} credits — {$tx['description']}\n";
}
echo "\n";

// List available packages
$packages = $client->credits->packages();
echo "Available packages:\n";
foreach ($packages as $pkg) {
    echo "  {$pkg['name']}: {$pkg['credits']} credits for {$pkg['currency']} " . number_format($pkg['price']) . "\n";
}
