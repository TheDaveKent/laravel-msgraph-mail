<?php

namespace InnoGE\LaravelMsGraphMail\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MicrosoftGraphApiService
{
    public string $tenantId = 'common';

    public function __construct(protected readonly string $clientId,
                                protected readonly string $clientSecret,
                                protected readonly int $accessTokenTtl
    ) {
    }

    /**
     * @throws RequestException
     */
    public function sendMail(string $from, array $payload): Response
    {
        return $this->getBaseRequest()
            ->post("/users/{$from}/sendMail", $payload)
            ->throw();
    }

    /**
     * @throws RequestException
     */
    public function send(string $from, string $id): Response
    {
        return $this->getBaseRequest()
            ->post("/users/{$from}/messages/{$id}/send")
            ->throw()
            ;
    }

    /**
     * @throws RequestException
     */
    public function draft(string $from, array $payload): Response
    {
        return $this->getBaseRequest()
            ->post("/users/{$from}/messages", $payload)
            ->throw()
            ;
    }

    protected function getBaseRequest(): PendingRequest
    {
        return Http::withToken($this->getAccessToken())
            ->withHeader('prefer', 'IdType="ImmutableId"')
            ->baseUrl('https://graph.microsoft.com/v1.0');
    }

    protected function getAccessToken(): string
    {
        return Cache::remember("microsoft-graph-api-access-token/{$this->tenantId}", $this->accessTokenTtl, function (): string {
            $response = Http::asForm()
                ->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
                    [
                        'grant_type' => 'client_credentials',
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                    ]);

            $response->throw();

            return $response->json('access_token');
        });
    }
}
