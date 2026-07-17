<?php

declare(strict_types=1);

namespace SendAfrica;

use SendAfrica\Exception\AuthenticationException;
use SendAfrica\Exception\InsufficientCreditsException;
use SendAfrica\Exception\RateLimitException;
use SendAfrica\Exception\SendAfricaException;
use SendAfrica\Exception\ServerException;
use SendAfrica\Exception\ValidationException;

class HttpClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct(string $apiKey, string $baseUrl = 'https://api.sendafrica.online', int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    public function get(string $path, array $queryParams = []): array
    {
        $url = $this->baseUrl . $path;
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPGET => true,
        ]);

        $response = $this->execute($ch);
        return $this->parseResponse($response);
    }

    public function post(string $path, array $data = [], array $extraHeaders = []): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array_merge($this->buildHeaders(), $extraHeaders),
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = $this->execute($ch);
        return $this->parseResponse($response);
    }

    public function delete(string $path): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->buildHeaders(),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        ]);

        $response = $this->execute($ch);
        return $this->parseResponse($response);
    }

    private function buildHeaders(): array
    {
        return [
            'X-API-Key: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: SendAfrica-PHP-SDK/1.0',
        ];
    }

    private function execute($ch): array
    {
        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $curlError) {
            throw new SendAfricaException("cURL error: {$curlError}", 'curl_error');
        }

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SendAfricaException("Invalid JSON response: {$body}", 'invalid_response');
        }

        $data['_http_code'] = $httpCode;
        return $data;
    }

    private function parseResponse(array $response): array
    {
        $httpCode = $response['_http_code'] ?? 0;
        unset($response['_http_code']);

        if (!empty($response['success'])) {
            return $response;
        }

        $errorCode = $response['error']['code'] ?? 'unknown_error';
        $errorMessage = $response['error']['message'] ?? 'An unknown error occurred';
        $requestId = $response['request_id'] ?? null;

        switch ($httpCode) {
            case 400:
                throw new ValidationException($errorMessage, $requestId);
            case 401:
                throw new AuthenticationException($errorMessage, $requestId);
            case 402:
                throw new InsufficientCreditsException($errorMessage, $requestId);
            case 429:
                throw new RateLimitException($errorMessage, $requestId);
            case 500:
                throw new ServerException($errorMessage, $requestId);
            default:
                throw new SendAfricaException($errorMessage, $errorCode, $requestId, $httpCode);
        }
    }
}
