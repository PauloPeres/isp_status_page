<?php
declare(strict_types=1);

namespace App\Integration\RestApi;

use App\Integration\AbstractIntegration;

/**
 * REST API Generic Adapter
 *
 * Provides a generic integration adapter for any REST API endpoint.
 * Supports configurable authentication, headers, and response validation.
 *
 * Configuration options:
 * - base_url: Base URL of the API
 * - method: HTTP method (GET, POST, etc.) - default: GET
 * - headers: Custom headers array
 * - auth_type: Authentication type (none, bearer, basic, api_key)
 * - api_key: API key or Bearer token
 * - username: Username for basic auth
 * - password: Password for basic auth
 * - timeout: Request timeout in seconds (default: 30)
 */
class RestApiAdapter extends AbstractIntegration
{
    /**
     * @var string
     */
    protected string $name = 'REST API';

    /**
     * @var string
     */
    protected string $type = 'rest_api';

    /**
     * REST API Client
     *
     * @var \App\Integration\RestApi\RestApiClient|null
     */
    protected ?RestApiClient $client = null;

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        try {
            $this->validateConfig(['base_url']);

            $headers = $this->getConfig('headers', []);
            if (is_string($headers)) {
                $decoded = json_decode($headers, true);
                $headers = is_array($decoded) ? $decoded : [];
            }

            $timeout = (int)$this->getConfig('timeout', 30);

            $this->client = new RestApiClient(
                $this->getConfig('base_url'),
                $headers,
                $timeout
            );

            // Configure authentication
            $this->configureAuth();

            $this->connected = true;
            $this->logInfo('REST API connection configured successfully');

            return true;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->setLastError($e->getMessage());
            $this->logError('Failed to configure REST API connection: ' . $e->getMessage());
            $this->connected = false;

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function testConnection(): array
    {
        try {
            if (!$this->connected) {
                $this->connect();
            }

            if ($this->client === null) {
                return $this->buildErrorResponse('Client not initialized');
            }

            $method = strtoupper($this->getConfig('method', 'GET'));
            $endpoint = $this->getConfig('test_endpoint', $this->getConfig('base_url'));

            $startTime = microtime(true);
            $response = $this->client->request($method, $endpoint);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 400) {
                return $this->buildSuccessResponse('Conexao estabelecida com sucesso', [
                    'status_code' => $statusCode,
                    'response_time' => $responseTime,
                ]);
            }

            return $this->buildErrorResponse(
                "API retornou status HTTP {$statusCode}",
                [
                    'status_code' => $statusCode,
                    'response_time' => $responseTime,
                ]
            );
        } catch (\Exception $e) {
            return $this->buildErrorResponse(
                'Falha na conexao: ' . $e->getMessage()
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getStatus(string $resourceId): array
    {
        try {
            if (!$this->connected) {
                $this->connect();
            }

            if ($this->client === null) {
                return $this->buildErrorResponse('Client not initialized');
            }

            $method = strtoupper($this->getConfig('method', 'GET'));
            $url = $this->getConfig('base_url');

            // Replace {resource_id} placeholder if present
            $url = str_replace('{resource_id}', $resourceId, $url);

            $startTime = microtime(true);
            $response = $this->client->request($method, $url);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $statusCode = $response->getStatusCode();
            $body = $response->getStringBody();

            // Validate response
            $validations = $this->validateResponse($response);

            return [
                'status' => $validations['success'] ? 'up' : 'down',
                'online' => $validations['success'],
                'message' => $validations['success'] ? 'OK' : ($validations['error'] ?? 'Validation failed'),
                'metadata' => [
                    'status_code' => $statusCode,
                    'response_time' => $responseTime,
                    'validations' => $validations['checks'] ?? [],
                    'body_length' => strlen($body),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'down',
                'online' => false,
                'message' => $e->getMessage(),
                'metadata' => [],
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getMetrics(string $resourceId, array $params = []): array
    {
        try {
            if (!$this->connected) {
                $this->connect();
            }

            if ($this->client === null) {
                return $this->buildErrorResponse('Client not initialized');
            }

            $method = strtoupper($this->getConfig('method', 'GET'));
            $url = $this->getConfig('base_url');
            $url = str_replace('{resource_id}', $resourceId, $url);

            $startTime = microtime(true);
            $response = $this->client->request($method, $url);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            $json = $response->getJson();

            return [
                'metrics' => is_array($json) ? $json : [],
                'timestamp' => date('c'),
                'resource_id' => $resourceId,
                'metadata' => [
                    'status_code' => $response->getStatusCode(),
                    'response_time' => $responseTime,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'metrics' => [],
                'timestamp' => date('c'),
                'resource_id' => $resourceId,
                'metadata' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): void
    {
        $this->client = null;
        $this->connected = false;
        $this->logDebug('REST API disconnected');
    }

    /**
     * Configure authentication on the client
     *
     * @return void
     */
    protected function configureAuth(): void
    {
        if ($this->client === null) {
            return;
        }

        $authType = $this->getConfig('auth_type', 'none');

        switch ($authType) {
            case 'bearer':
                $token = $this->getConfig('api_key', '');
                if (!empty($token)) {
                    $this->client->setBearerToken($token);
                }
                break;

            case 'basic':
                $username = $this->getConfig('username', '');
                $password = $this->getConfig('password', '');
                if (!empty($username)) {
                    $this->client->setBasicAuth($username, $password);
                }
                break;

            case 'api_key':
                $apiKey = $this->getConfig('api_key', '');
                $headerName = $this->getConfig('api_key_header', 'X-API-Key');
                if (!empty($apiKey)) {
                    $this->client->setHeader($headerName, $apiKey);
                }
                break;

            case 'none':
            default:
                break;
        }
    }

    /**
     * Validate API response against configured validators
     *
     * @param \Cake\Http\Client\Response $response HTTP response
     * @return array{success: bool, checks: array, error?: string}
     */
    public function validateResponse(\Cake\Http\Client\Response $response): array
    {
        $validations = ['success' => true, 'checks' => []];

        // Status code validation
        $expectedStatus = $this->getConfig('expected_status');
        if ($expectedStatus !== null) {
            $expectedStatuses = is_array($expectedStatus) ? $expectedStatus : [(int)$expectedStatus];
            $check = in_array($response->getStatusCode(), $expectedStatuses, true);
            $validations['checks'][] = [
                'type' => 'status_code',
                'passed' => $check,
                'expected' => $expectedStatuses,
                'actual' => $response->getStatusCode(),
            ];
            if (!$check) {
                $validations['success'] = false;
                $validations['error'] = "Status code {$response->getStatusCode()} does not match expected";
            }
        }

        // JSON path validation
        $jsonPath = $this->getConfig('json_path');
        if ($jsonPath !== null) {
            $json = $response->getJson();
            $actualValue = is_array($json) ? $this->resolveJsonPath($json, $jsonPath) : null;
            $expectedValue = $this->getConfig('expected_value');
            $check = ($actualValue !== null && (string)$actualValue === (string)$expectedValue);
            $validations['checks'][] = [
                'type' => 'json_path',
                'passed' => $check,
                'path' => $jsonPath,
                'expected' => $expectedValue,
                'actual' => $actualValue,
            ];
            if (!$check) {
                $validations['success'] = false;
                $validations['error'] = "JSON path '{$jsonPath}' value does not match expected";
            }
        }

        // Content contains validation
        $contentContains = $this->getConfig('content_contains');
        if ($contentContains !== null) {
            $body = $response->getStringBody();
            $check = str_contains($body, $contentContains);
            $validations['checks'][] = [
                'type' => 'content_contains',
                'passed' => $check,
                'expected' => $contentContains,
            ];
            if (!$check) {
                $validations['success'] = false;
                $validations['error'] = "Response body does not contain expected content";
            }
        }

        return $validations;
    }

    /**
     * Resolve a dot-notation path in a JSON array
     *
     * @param array<string, mixed> $data JSON data
     * @param string $path Dot-notation path (e.g. "status.health")
     * @return mixed Value at the path, or null if not found
     */
    public function resolveJsonPath(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    /**
     * Get the REST API client instance
     *
     * @return \App\Integration\RestApi\RestApiClient|null
     */
    public function getClient(): ?RestApiClient
    {
        return $this->client;
    }

    /**
     * Set the REST API client (for testing)
     *
     * @param \App\Integration\RestApi\RestApiClient $client Client instance
     * @return void
     */
    public function setClient(RestApiClient $client): void
    {
        $this->client = $client;
        $this->connected = true;
    }
}
