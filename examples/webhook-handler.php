<?php
/**
 * Webhook handler example (Laravel/Slim style).
 *
 * NOTE: Webhooks are speculative -- the SendAfrica API does not yet
 * forward signed events to customer endpoints. This code is ready
 * for when that ships server-side.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;

$client = new SendAfrica(webhook_secret: 'whsec_your_secret_here');

// In a real framework, get the raw body and signature from the request:
$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SENDAFRICA_SIGNATURE'] ?? null;

$event = $client->webhooks->parse(payload: $rawBody, signature: $signature);

switch ($event->type) {
    case 'sms.delivered':
        echo "Message {$event->messageId} delivered\n";
        break;
    case 'sms.failed':
        echo "Message {$event->messageId} failed\n";
        break;
    default:
        echo "Unknown event: {$event->type}\n";
}
