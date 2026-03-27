<?php
declare(strict_types=1);

namespace App\Integration\Zabbix;

use Cake\Http\Client;
use Cake\Log\Log;

/**
 * ZabbixClient
 *
 * JSON-RPC 2.0 client for communicating with the Zabbix API.
 * Handles authentication, request formatting, and response parsing.
 *
 * @see docs/API_INTEGRATIONS.md for Zabbix API specifications
 */
class ZabbixClient
{
    /**
     * Zabbix API base URL
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * HTTP client instance
     *
     * @var \Cake\Http\Client
     */
    protected Client $httpClient;

    /**
     * Authentication token
     *
     * @var string|null
     */
    protected ?string $authToken = null;

    /**
     * JSON-RPC request ID counter
     *
     * @var int
     */
    protected int $requestId = 0;

    /**
     * Request timeout in seconds
     *
     * @var int
     */
    protected int $timeout;

    /**
     * Constructor
     *
     * @param string $baseUrl Zabbix API URL (e.g., https://zabbix.example.com/api_jsonrpc.php)
     * @param \Cake\Http\Client|null $httpClient HTTP client instance (for testing)
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(string $baseUrl, ?Client $httpClient = null, int $timeout = 30)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->httpClient = $httpClient ?? new Client();
        $this->timeout = $timeout;
    }

    /**
     * Authenticate with Zabbix API
     *
     * Uses the user.login method to obtain an authentication token.
     *
     * @param string $username Zabbix username
     * @param string $password Zabbix password
     * @return string Authentication token
     * @throws \RuntimeException If authentication fails
     */
    public function login(string $username, string $password): string
    {
        Log::debug('Attempting Zabbix API login', ['url' => $this->baseUrl]);

        $result = $this->callRaw('user.login', [
            'username' => $username,
            'password' => $password,
        ]);

        if (empty($result)) {
            throw new \RuntimeException('Zabbix authentication failed: empty token received');
        }

        $this->authToken = $result;

        Log::info('Zabbix API login successful');

        return $this->authToken;
    }

    /**
     * Logout from Zabbix API
     *
     * Invalidates the current authentication token.
     *
     * @return void
     */
    public function logout(): void
    {
        if ($this->authToken === null) {
            return;
        }

        try {
            $this->call('user.logout', []);
        } catch (\Exception $e) {
            Log::warning('Zabbix logout failed: ' . $e->getMessage());
        }

        $this->authToken = null;
    }

    /**
     * Make an authenticated JSON-RPC call to Zabbix API
     *
     * @param string $method Zabbix API method (e.g., 'host.get', 'trigger.get')
     * @param array<string, mixed> $params Method parameters
     * @return mixed API response result
     * @throws \RuntimeException If not authenticated or call fails
     */
    public function call(string $method, array $params = []): mixed
    {
        if ($this->authToken === null && $method !== 'user.login') {
            throw new \RuntimeException('Not authenticated. Call login() first.');
        }

        return $this->callRaw($method, $params, $this->authToken);
    }

    /**
     * Make a raw JSON-RPC 2.0 call to Zabbix API
     *
     * @param string $method JSON-RPC method
     * @param array<string, mixed> $params Method parameters
     * @param string|null $auth Authentication token
     * @return mixed Parsed result from JSON-RPC response
     * @throws \RuntimeException If the request fails or returns an error
     */
    protected function callRaw(string $method, array $params, ?string $auth = null): mixed
    {
        $this->requestId++;

        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $this->requestId,
        ];

        if ($auth !== null) {
            $payload['auth'] = $auth;
        }

        Log::debug("Zabbix API call: {$method}", [
            'request_id' => $this->requestId,
        ]);

        try {
            $response = $this->httpClient->post(
                $this->baseUrl,
                json_encode($payload),
                [
                    'type' => 'application/json-rpc',
                    'timeout' => $this->timeout,
                    'headers' => [
                        'Content-Type' => 'application/json-rpc',
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::error("Zabbix API request failed: {$e->getMessage()}", [
                'method' => $method,
                'url' => $this->baseUrl,
            ]);

            throw new \RuntimeException(
                "Zabbix API request failed: {$e->getMessage()}",
                0,
                $e
            );
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException(
                "Zabbix API returned HTTP {$statusCode}"
            );
        }

        $body = $response->getStringBody();
        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('Zabbix API returned invalid JSON response');
        }

        // Check for JSON-RPC error
        if (isset($decoded['error'])) {
            $error = $decoded['error'];
            $errorMessage = sprintf(
                'Zabbix API error: %s - %s',
                $error['message'] ?? 'Unknown error',
                $error['data'] ?? ''
            );

            Log::error($errorMessage, [
                'method' => $method,
                'error_code' => $error['code'] ?? null,
            ]);

            throw new \RuntimeException($errorMessage);
        }

        return $decoded['result'] ?? null;
    }

    /**
     * Check if client is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        return $this->authToken !== null;
    }

    /**
     * Get the current authentication token
     *
     * @return string|null
     */
    public function getAuthToken(): ?string
    {
        return $this->authToken;
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
}
