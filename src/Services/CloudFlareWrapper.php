<?php

namespace Netnak\CloudBurst\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;

class CloudFlareWrapper
{
    public const TIMEOUT = 20;

    private ?string $access_key;
    private string $endpoint;

    private bool $request_successful = false;
    private string $last_error = '';
    private array $last_response = [];
    private array $last_request = [];

    public function __construct(
        ?string $access_key = null,
        string $endpoint = 'https://api.cloudflare.com/client/v4/'
    ) {
        $this->endpoint = rtrim($endpoint, '/') . '/';

        $access_key = $access_key ?? config('cloudburst.access_key');
     
        if (!$access_key) {
            throw new \InvalidArgumentException("Cloudflare API token missing.");
        } else {
            $this->access_key = $access_key;
        }
    }

    public function success(): bool
    {
        return $this->request_successful;
    }

    public function getLastError(): string|false
    {
        return $this->last_error ?: false;
    }

    public function getLastResponse(): array
    {
        return $this->last_response;
    }

    public function getLastRequest(): array
    {
        return $this->last_request;
    }

    public function get(string $method, array $params = [], int $timeout = self::TIMEOUT): array|false
    {
        return $this->request('GET', $method, $params, $timeout);
    }

    public function post(string $method, array $payload = [], int $timeout = self::TIMEOUT): array|false
    {
        return $this->request('POST', $method, $payload, $timeout);
    }

    public function put(string $method, array $payload = [], int $timeout = self::TIMEOUT): array|false
    {
        return $this->request('PUT', $method, $payload, $timeout);
    }

    public function patch(string $method, array $payload = [], int $timeout = self::TIMEOUT): array|false
    {
        return $this->request('PATCH', $method, $payload, $timeout);
    }

    public function delete(string $method, array $payload = [], int $timeout = self::TIMEOUT): array|false
    {
        return $this->request('DELETE', $method, $payload, $timeout);
    }

    private function request(string $verb, string $method, array $data = [], int $timeout = self::TIMEOUT): array|false
    {
        $this->last_error = '';
        $this->request_successful = false;
        $url = $this->endpoint . ltrim($method, '/');

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $headers['Authorization'] = 'Bearer ' . $this->access_key;

        $this->last_request = [
            'method' => $verb,
            'url' => $url,
            'payload' => $data,
        ];

        try {
            $http = Http::withHeaders($headers)
                ->timeout($timeout)
                ->acceptJson();

            /** @var Response $response */
            $response = match (strtoupper($verb)) {
                'GET'    => $http->get($url, $data),
                'POST'   => $http->post($url, $data),
                'PUT'    => $http->put($url, $data),
                'PATCH'  => $http->patch($url, $data),
                'DELETE' => $http->delete($url, $data),
                default  => throw new \InvalidArgumentException("Unsupported HTTP verb: $verb"),
            };

            //dd($response->body());

            $this->last_response = [
                'status' => $response->status(),
                'body' => $response->json(),
                'headers' => $response->headers(),
            ];

            $this->request_successful = $response->successful();

            if (!$response->successful()) {
                $this->last_error = $response->body();
                return false;
            }

            return $response->json();
        } catch (RequestException $e) {
            $this->last_error = $e->getMessage();
            $this->last_response = [];
            return false;
        }
    }
}
