<?php
/**
 * Send bulk SMS with partial failure handling.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;

$client = new SendAfrica();

$results = $client->sms->sendMany(
    messages: [
        ['to' => '0711111111', 'message' => 'Hello John'],
        ['to' => '0722222222', 'message' => 'Hello Mary'],
        ['to' => '+255733333333', 'message' => 'Hello Alex'],
    ],
    sender: 'MyBrand'
);

echo "Sent: {$results->getSentCount()}\n";
echo "Failed: {$results->getFailedCount()}\n";

foreach ($results->failed as $failure) {
    echo "  [{$failure['index']}] {$failure['to']}: {$failure['error']}\n";
}
