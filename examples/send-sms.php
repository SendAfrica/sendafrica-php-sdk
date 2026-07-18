<?php
/**
 * Send a single SMS.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;
use SendAfrica\Exceptions\SendAfricaException;

$client = new SendAfrica();

try {
    $result = $client->sms->send(to: '0712345678', message: 'Welcome to SendAfrica');
    echo $result->messageId . "\n";
    echo $result->status . "\n";
    echo $result->creditsUsed . "\n";
} catch (SendAfricaException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
