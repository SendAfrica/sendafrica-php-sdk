<?php

declare(strict_types=1);

namespace SendAfrica;

use SendAfrica\Exceptions\AuthenticationException;
use SendAfrica\Resources\AuthResource;
use SendAfrica\Resources\CreditsResource;
use SendAfrica\Resources\PaymentsResource;
use SendAfrica\Resources\SmsResource;
use SendAfrica\Resources\WebhooksResource;

class SendAfrica
{
    public SmsResource $sms;
    public CreditsResource $credits;
    public PaymentsResource $payments;
    public WebhooksResource $webhooks;
    public AuthResource $auth;
    public string $environment;

    private HttpClient $http;

    /**
     * @param string|null $apiKey      Your SendAfrica API key (or set SENDAFRICA_API_KEY env var)
     * @param string      $baseUrl     API base URL
     * @param int         $timeout     Request timeout in seconds
     * @param int         $maxRetries  Max retry attempts on 429/5xx errors
     * @param string      $environment Label for logging/display
     * @param bool        $debug       Print request/response logs
     * @param string|null $webhookSecret HMAC secret for webhook signature verification
     */
    public function __construct(
        ?string $apiKey = null,
        string $baseUrl = 'https://api.sendafrica.online/v1',
        int $timeout = 10,
        int $maxRetries = 3,
        string $environment = 'production',
        bool $debug = false,
        ?string $webhookSecret = null
    ) {
        $resolvedKey = $apiKey ?? getenv('SENDAFRICA_API_KEY') ?: null;

        if ($resolvedKey === null || $resolvedKey === '') {
            throw new AuthenticationException(
                'No API key provided. Pass an api_key argument or set the SENDAFRICA_API_KEY environment variable.'
            );
        }

        $this->http = new HttpClient($resolvedKey, $baseUrl, $timeout, $maxRetries, $debug);
        $this->environment = $environment;

        $this->sms = new SmsResource($this->http);
        $this->credits = new CreditsResource($this->http);
        $this->payments = new PaymentsResource($this->http);
        $this->webhooks = new WebhooksResource($webhookSecret);
        $this->auth = new AuthResource($this->sms);
    }
}
