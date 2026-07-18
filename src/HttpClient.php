<?php

declare(strict_types=1);

namespace SendAfrica;

use SendAfrica\Exceptions\AuthenticationException;
use SendAfrica\Exceptions\ConnectionException;
use SendAfrica\Exceptions\InsufficientCreditsException;
use SendAfrica\Exceptions\NotFoundException;
use SendAfrica\Exceptions\RateLimitException;
use SendAfrica\Exceptions\SendAfricaException;
use SendAfrica\Exceptions\ServerException;
use SendAfrica\Exceptions\ValidationException;

class HttpClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private int $maxRetries;
    private bool $debug;

    private const MAX_BACKOFF = 8.0;
    private const BASE_BACKOFF = 0.5;
    private const RETRY_STATUSES = [429, 500, 502, 503, 504];

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://api.sendafrica.online/v1',
        int $timeout = 10,
        int $maxRetries = 3,
        bool $debug = false
    ) {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
        $this->debug = $debug;
    }

    public function get(string $path, array $queryParams = []): array
    {
        $url = $this->baseUrl . $path;
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $this->request('GET', $url);
    }

    public function post(string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;
        return $this->request('POST', $url, $data);
    }

    public function delete(string $path): array
    {
        $url = $this->baseUrl . $path;
        return $this->request('DELETE', $url);
    }

    private function request(string $method, string $url, ?array $body = null): array
    {
        $attempt = 0;
        $lastException = null;

        while (true) {
            $attempt++;
            $requestId = bin2hex(random_bytes(16));

            if ($this->debug) {
                error_log("[sendafrica] {$method} {$url} (attempt {$attempt})");
            }

            try {
                $response = $this->doRequest($method, $url, $body, $requestId);
                $data = $this->parseResponse($response, $requestId);

                if ($this->debug) {
                    error_log("[sendafrica] Response: " . json_encode($data));
                }

                return $data;
            } catch (SendAfricaException $e) {
                $lastException = $e;
                $shouldRetry = $this->shouldRetry($e, $attempt);

                if (!$shouldRetry) {
                    throw $e;
                }

                $wait = $this->getRetryWait($e, $attempt);

                if ($this->debug) {
                    error_log("[sendafrica] Retrying in {$wait}s after {$e->getErrorCode()}");
                }

                usleep((int) ($wait * 1_000_000));
            }
        }
    }

    private function doRequest(string $method, string $url, ?array $body, string $requestId): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: sendafrica-php/1.0',
            'X-Request-Id: ' . $requestId,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($body !== null) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            default:
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
        }

        $responseBody = curl_exec($ch);

        if ($responseBody === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new ConnectionException("cURL error ({$errno}): {$error}");
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $allHeaders = $this->parseResponseHeaders($ch);
        curl_close($ch);

        return [
            'body' => $responseBody,
            'http_code' => $httpCode,
            'headers' => $allHeaders,
        ];
    }

    private function parseResponseHeaders($ch): array
    {
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);

        $headers = [];
        if (is_string($rawHeaders)) {
            foreach (explode("\r\n", $rawHeaders) as $line) {
                if (strpos($line, ':') !== false) {
                    [$key, $value] = explode(':', $line, 2);
                    $headers[trim($key)] = trim($value);
                }
            }
        }

        return $headers;
    }

    private function parseResponse(array $response, string $requestId): array
    {
        $httpCode = $response['http_code'];
        $body = $response['body'];
        $headers = $response['headers'];

        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new SendAfricaException(
                "Invalid JSON response: {$body}",
                'invalid_response',
                $requestId,
                $httpCode
            );
        }

        $responseId = $data['request_id'] ?? $requestId;

        if (isset($data['success']) && $data['success'] === true) {
            return $data;
        }

        $errorCode = $data['error']['code'] ?? 'unknown_error';
        $errorMessage = $data['error']['message'] ?? 'An unknown error occurred';
        $responseBody = $body;

        switch ($httpCode) {
            case 400:
                throw new ValidationException($errorMessage, $responseId);
            case 401:
                throw new AuthenticationException($errorMessage, $responseId);
            case 402:
                throw new InsufficientCreditsException($errorMessage, $responseId);
            case 404:
                throw new NotFoundException($errorMessage, $responseId);
            case 429:
                $retryAfter = null;
                if (isset($headers['Retry-After'])) {
                    $retryAfter = (float) $headers['Retry-After'];
                }
                throw new RateLimitException($errorMessage, $responseId, $retryAfter);
            case 500:
            case 502:
            case 503:
            case 504:
                throw new ServerException($errorMessage, $responseId, $httpCode);
            default:
                throw new SendAfricaException($errorMessage, $errorCode, $responseId, $httpCode, $responseBody);
        }
    }

    private function shouldRetry(SendAfricaException $e, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        if ($e instanceof RateLimitException) {
            return true;
        }

        if ($e instanceof ServerException) {
            return true;
        }

        return false;
    }

    private function getRetryWait(SendAfricaException $e, int $attempt): float
    {
        if ($e instanceof RateLimitException && $e->getRetryAfter() !== null) {
            return $e->getRetryAfter();
        }

        $backoff = self::BASE_BACKOFF * pow(2, $attempt - 1);
        return min($backoff, self::MAX_BACKOFF);
    }
}
