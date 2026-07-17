<?php
/**
 * SendAfrica PHP SDK — Basic SMS Example
 *
 * This example shows how to:
 * 1. Initialize the client
 * 2. Send a single SMS
 * 3. Check your credit balance
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\Client;
use SendAfrica\Exception\SendAfricaException;

// Initialize the client with your API key
$client = new Client('YOUR_API_KEY_HERE');

try {
    // Check balance first
    $credits = $client->getBalance();
    echo "Your balance: {$credits} credits\n";

    // Send an SMS
    $result = $client->send('0712345678', 'Hello from my PHP app!');
    echo "SMS sent!\n";
    echo "  Message ID: {$result['message_id']}\n";
    echo "  Status: {$result['status']}\n";
    echo "  Credits used: {$result['credits_used']}\n";

} catch (SendAfricaException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getErrorCode() . "\n";
}
