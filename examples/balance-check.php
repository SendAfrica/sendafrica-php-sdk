<?php
/**
 * Check credit balance and view transaction history.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;

$client = new SendAfrica();

$balance = $client->credits->balance();
echo "Account: {$balance->accountId}\n";
echo "Balance: {$balance->balance}\n\n";

$transactions = $client->credits->history(page: 1, per_page: 5);
foreach ($transactions as $tx) {
    $sign = $tx->amount > 0 ? '+' : '';
    echo "{$sign}{$tx->amount} credits — {$tx->description}\n";
}
