<?php
declare(strict_types=1);

namespace App\Integration\Ixc;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Log\Log;

/**
 * IXC API Client
 *
 * HTTP client wrapping CakePHP Http\Client for communicating with IXC Soft API.
 * Handles token-based authentication via BASE64(user:password_md5) query parameter.
 */
class IxcClient
{
    /**
     * CakePHP HTTP Client
     *
     * @var \Cake\Http\Client
     */
    protected Client $httpClient;

    /**
     * Base URL of the IXC API
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * API username
     *
     * @var string
     */
    protected string $username;

    /**
     * API password (plain or MD5)
     *
     * @var string
     */
    protected string $password;

    /**
     * Generated authentication token
     *
     * @var string|null
     */
    protected ?string $token = null;

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Whether we are authenticated
     *
     * @var bool
     */
    protected bool $authenticated = false;

    /**
     * Constructor
     *
     * @param string $baseUrl Base URL of the IXC API
     * @param string $username API username
     * @param string $password API password
     * @param int $timeout Request timeout in seconds
     * @param \Cake\Http\Client|null $httpClient Optional HTTP client (for testing)
     */
    public function __construct(
        string $baseUrl,
        string $username,
        string $password,
        int $timeout = 30,
        ?Client $httpClient = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => $timeout,
        ]);

        $this->generateToken();
    }

    /**
     * Generate the authentication token
     *
     * IXC uses token-based auth via ?token=BASE64(user:password_md5)
     *
     * @return void
     */
    protected function generateToken(): void
    {
        $passwordMd5 = md5($this->password);
        $this->token = base64_encode("{$this->username}:{$passwordMd5}");
    }

    /**
     * Authenticate with the IXC API
     *
     * Tests authentication by making a request to the auth endpoint.
     *
     * @return bool True if authentication successful
     * @throws \RuntimeException If authentication fails
     */
    public function authenticate(): bool
    {
        try {
            $response = $this->get('/auth');

            $this->authenticated = true;

            Log::info('IXC authentication successful', [
                'base_url' => $this->baseUrl,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->authenticated = false;

            Log::error('IXC authentication failed', [
                'base_url' => $this->baseUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'IXC authentication failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Execute a GET request
     *
     * @param string $endpoint API endpoint (e.g., '/services/12345/status')
     * @param array<string, mixed> $params Query parameters
     * @return array Response data
     * @throws \RuntimeException If request fails
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Execute a POST request
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $data Request body data
     * @return array Response data
     * @throws \RuntimeException If request fails
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    /**
     * Execute an HTTP request to the IXC API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $params Query parameters
     * @param array<string, mixed> $data Request body data
     * @return array Response data
     * @throws \RuntimeException If request fails
     */
    protected function request(
        string $method,
        string $endpoint,
        array $params = [],
        array $data = []
    ): array {
        $url = $this->buildUrl($endpoint, $params);

        $options = [
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        try {
            Log::debug("IXC API request: {$method} {$endpoint}", [
                'params' => $params,
            ]);

            $startTime = microtime(true);

            if ($method === 'POST') {
                $response = $this->httpClient->post($url, json_encode($data), $options);
            } else {
                $response = $this->httpClient->get($url, [], $options);
            }

            $responseTime = (microtime(true) - $startTime) * 1000;

            return $this->handleResponse($response, $endpoint, $responseTime);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("IXC API request failed: {$endpoint}", [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "IXC API request failed: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build the full URL with token authentication
     *
     * @param string $endpoint API endpoint
     * @param array<string, mixed> $params Additional query parameters
     * @return string Full URL
     */
    protected function buildUrl(string $endpoint, array $params = []): string
    {
        $endpoint = '/' . ltrim($endpoint, '/');
        $url = $this->baseUrl . $endpoint;

        // Add token to query parameters
        $params['token'] = $this->token;

        $queryString = http_build_query($params);

        return $url . '?' . $queryString;
    }

    /**
     * Handle API response
     *
     * @param \Cake\Http\Client\Response $response HTTP response
     * @param string $endpoint API endpoint for logging
     * @param float $responseTime Response time in milliseconds
     * @return array Parsed response data
     * @throws \RuntimeException If response indicates an error
     */
    protected function handleResponse(Response $response, string $endpoint, float $responseTime): array
    {
        $statusCode = $response->getStatusCode();

        Log::debug("IXC API response: {$statusCode} for {$endpoint}", [
            'response_time' => round($responseTime, 2),
        ]);

        // Check for authentication errors
        if ($statusCode === 401 || $statusCode === 403) {
            $this->authenticated = false;

            throw new \RuntimeException(
                "IXC authentication error ({$statusCode}): Access denied"
            );
        }

        // Check for server errors
        if ($statusCode >= 500) {
            throw new \RuntimeException(
                "IXC server error ({$statusCode}): Internal server error"
            );
        }

        // Check for client errors
        if ($statusCode >= 400) {
            $body = $response->getStringBody();
            $error = json_decode($body, true);
            $message = $error['message'] ?? $error['error'] ?? "Request failed ({$statusCode})";

            throw new \RuntimeException("IXC API error: {$message}");
        }

        // Parse response body
        $body = $response->getStringBody();
        $data = json_decode($body, true);

        if (!is_array($data)) {
            throw new \RuntimeException('IXC API returned invalid JSON response');
        }

        $data['_response_time'] = round($responseTime, 2);
        $data['_status_code'] = $statusCode;

        return $data;
    }

    /**
     * Check if currently authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
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
     * Get the authentication token
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }
}
