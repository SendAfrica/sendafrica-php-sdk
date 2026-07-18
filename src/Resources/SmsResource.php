<?php

declare(strict_types=1);

namespace SendAfrica\Resources;

use SendAfrica\HttpClient;
use SendAfrica\Models\BulkSmsResult;
use SendAfrica\Models\SmsAnalysis;
use SendAfrica\Models\SmsResult;
use SendAfrica\Utils\PhoneNormalizer;
use SendAfrica\Utils\SmsAnalyzer;

class SmsResource
{
    private HttpClient $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Send a single SMS. Phone numbers are normalized to E.164 locally before any network call.
     */
    public function send(string $to, string $message, ?string $sender = null): SmsResult
    {
        $normalized = PhoneNormalizer::normalize($to);

        $payload = [
            'to' => $normalized,
            'message' => $message,
        ];

        if ($sender !== null) {
            $payload['from'] = $sender;
        }

        $response = $this->http->post('/sms', $payload);
        $data = $response['data'] ?? $response;

        return new SmsResult(array_merge($data, ['to' => $normalized]));
    }

    /**
     * Send multiple SMS. Each failure is collected individually -- the batch
     * does not abort on a single bad number.
     *
     * @param array<int, array{to: string, message: string, sender?: string}> $messages
     * @param float $rateLimitPerSec Client-side rate limit (requests per second)
     */
    public function sendMany(array $messages, ?string $sender = null, float $rateLimitPerSec = 10.0): BulkSmsResult
    {
        $results = [];
        $failed = [];
        $delay = $rateLimitPerSec > 0 ? 1.0 / $rateLimitPerSec : 0;

        foreach ($messages as $index => $msg) {
            $to = $msg['to'] ?? '';
            $message = $msg['message'] ?? '';
            $msgSender = $msg['sender'] ?? $sender;

            try {
                $result = $this->send($to, $message, $msgSender);
                $results[] = $result;
            } catch (\Exception $e) {
                $failed[] = [
                    'index' => $index,
                    'to' => $to,
                    'error' => $e->getMessage(),
                ];
            }

            if ($delay > 0 && $index < count($messages) - 1) {
                usleep((int) ($delay * 1_000_000));
            }
        }

        return new BulkSmsResult($results, $failed);
    }

    /**
     * Analyze a message for encoding, segment count, and credit cost.
     * Makes zero network calls -- pure local computation.
     */
    public function analyze(string $message): SmsAnalysis
    {
        $analysis = SmsAnalyzer::analyze($message);

        return new SmsAnalysis(
            $analysis['encoding'],
            $analysis['characters'],
            $analysis['parts'],
            $analysis['credits']
        );
    }
}
