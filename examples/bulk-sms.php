<?php
/**
 * SendAfrica PHP SDK — Bulk SMS Example
 *
 * Send the same message to multiple recipients at once (max 100).
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\Client;
use SendAfrica\Exception\InsufficientCreditsException;
use SendAfrica\Exception\SendAfricaException;

$client = new Client('YOUR_API_KEY_HERE');

$recipients = [
    '0712345678',
    '0754000111',
    '0789012345',
    '0767890123',
];

try {
    $result = $client->sms->sendBulk(
        to: $recipients,
        message: 'Flash sale! 50% off everything today only.',
        from: 'MyShop'
    );

    echo "Bulk SMS Results:\n";
    echo "  Total: {$result['total']}\n";
    echo "  Sent: {$result['sent']}\n";
    echo "  Failed: {$result['failed']}\n";

    // Check individual results
    foreach ($result['results'] as $r) {
        $status = $r['status'] === 'sent' ? '✓' : '✗';
        echo "  {$status} {$r['to']} — {$r['status']}\n";
    }

} catch (InsufficientCreditsException $e) {
    echo "Not enough credits! Top up at https://app.sendafrica.online\n";
} catch (SendAfricaException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
