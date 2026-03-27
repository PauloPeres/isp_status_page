<?php
declare(strict_types=1);

namespace App\Integration\RestApi;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Log\Log;

/**
 * REST API Client
 *
 * Generic HTTP client for communicating with external REST APIs.
 * Wraps CakePHP's Http\Client with common functionality for
 * authentication, headers, and error handling.
 */
class RestApiClient
{
    /**
     * HTTP Client instance
     *
     * @var \Cake\Http\Client
     */
    protected Client $http;

    /**
     * Base URL for API requests
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Default headers for all requests
     *
     * @var array<string, string>
     */
    protected array $headers = [];

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Constructor
     *
     * @param string $baseUrl Base URL for the API
     * @param array<string, string> $headers Default headers
     * @param int $timeout Request timeout in seconds
     * @param \Cake\Http\Client|null $client HTTP client instance (for testing)
     */
    public function __construct(
        string $baseUrl,
        array $headers = [],
        int $timeout = 30,
        ?Client $client = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->headers = array_merge([
            'User-Agent' => 'ISP-Status-Page/1.0',
            'Accept' => 'application/json',
        ], $headers);
        $this->timeout = $timeout;
        $this->http = $client ?? new Client([
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Make a GET request
     *
     * @param string $endpoint API endpoint (relative or absolute URL)
     * @param array<string, mixed> $queryParams Query parameters
     * @return \Cake\Http\Client\Response
     */
    public function get(string $endpoint, array $queryParams = []): Response
    {
        $url = $this->buildUrl($endpoint);

        Log::debug("RestApiClient GET: {$url}", [
            'query_params' => $queryParams,
        ]);

        return $this->http->get($url, $queryParams, [
            'headers' => $this->headers,
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Make a POST request
     *
     * @param string $endpoint API endpoint (relative or absolute URL)
     * @param array<string, mixed>|string $data Request body
     * @return \Cake\Http\Client\Response
     */
    public function post(string $endpoint, array|string $data = []): Response
    {
        $url = $this->buildUrl($endpoint);

        Log::debug("RestApiClient POST: {$url}");

        return $this->http->post($url, $data, [
            'headers' => $this->headers,
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Make a PUT request
     *
     * @param string $endpoint API endpoint (relative or absolute URL)
     * @param array<string, mixed>|string $data Request body
     * @return \Cake\Http\Client\Response
     */
    public function put(string $endpoint, array|string $data = []): Response
    {
        $url = $this->buildUrl($endpoint);

        Log::debug("RestApiClient PUT: {$url}");

        return $this->http->put($url, $data, [
            'headers' => $this->headers,
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Make a DELETE request
     *
     * @param string $endpoint API endpoint (relative or absolute URL)
     * @return \Cake\Http\Client\Response
     */
    public function delete(string $endpoint): Response
    {
        $url = $this->buildUrl($endpoint);

        Log::debug("RestApiClient DELETE: {$url}");

        return $this->http->delete($url, [], [
            'headers' => $this->headers,
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Make a request with any HTTP method
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
     * @param string $endpoint API endpoint
     * @param array<string, mixed>|string $data Request body
     * @return \Cake\Http\Client\Response
     */
    public function request(string $method, string $endpoint, array|string $data = []): Response
    {
        $method = strtoupper($method);
        $url = $this->buildUrl($endpoint);

        Log::debug("RestApiClient {$method}: {$url}");

        return match ($method) {
            'GET' => $this->http->get($url, is_array($data) ? $data : [], [
                'headers' => $this->headers,
                'timeout' => $this->timeout,
            ]),
            'POST' => $this->http->post($url, $data, [
                'headers' => $this->headers,
                'timeout' => $this->timeout,
            ]),
            'PUT' => $this->http->put($url, $data, [
                'headers' => $this->headers,
                'timeout' => $this->timeout,
            ]),
            'DELETE' => $this->http->delete($url, [], [
                'headers' => $this->headers,
                'timeout' => $this->timeout,
            ]),
            'PATCH' => $this->http->patch($url, $data, [
                'headers' => $this->headers,
                'timeout' => $this->timeout,
            ]),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Set a default header
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return void
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Set authorization header with Bearer token
     *
     * @param string $token Bearer token
     * @return void
     */
    public function setBearerToken(string $token): void
    {
        $this->headers['Authorization'] = 'Bearer ' . $token;
    }

    /**
     * Set basic authentication header
     *
     * @param string $username Username
     * @param string $password Password
     * @return void
     */
    public function setBasicAuth(string $username, string $password): void
    {
        $this->headers['Authorization'] = 'Basic ' . base64_encode("{$username}:{$password}");
    }

    /**
     * Get the base URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get current headers
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Build full URL from endpoint
     *
     * @param string $endpoint Endpoint path (relative or absolute)
     * @return string Full URL
     */
    protected function buildUrl(string $endpoint): string
    {
        // If endpoint is already a full URL, use it directly
        if (preg_match('~^https?://~i', $endpoint)) {
            return $endpoint;
        }

        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }
}
