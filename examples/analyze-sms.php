<?php
/**
 * Preview SMS encoding, segments, and cost without any network call.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SendAfrica\SendAfrica;

$client = new SendAfrica();

$analysis = $client->sms->analyze('Habari, how are you?');
echo "Encoding: {$analysis->encoding}\n";
echo "Characters: {$analysis->characters}\n";
echo "Parts: {$analysis->parts}\n";
echo "Credits: {$analysis->credits}\n\n";

$emoji = $client->sms->analyze("Habari \xF0\x9F\x98\x8A");
echo "Encoding: {$emoji->encoding}\n";
echo "Characters: {$emoji->characters}\n";
echo "Parts: {$emoji->parts}\n";
echo "Credits: {$emoji->credits}\n";
