<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Integration\RestApi\RestApiAdapter;
use App\Model\Entity\Monitor;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * REST API Checker
 *
 * Checks API endpoints with configurable validation rules.
 * Monitor type: 'api'
 *
 * Configuration options (in monitor's configuration JSON):
 * - url: The API endpoint URL
 * - method: HTTP method (default: GET)
 * - headers: Custom request headers
 * - status_code: Expected HTTP status code (default: 200)
 * - json_path: Dot-notation path to check in JSON response
 * - expected_value: Value to match at json_path
 * - content_contains: Substring to find in response body
 * - auth_type: Authentication type (none, bearer, basic, api_key)
 * - api_key: API key or Bearer token
 * - username: Username for basic auth
 * - password: Password for basic auth
 * - integration_id: Optional integration to use for connection config
 */
class RestApiChecker extends AbstractChecker
{
    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'api';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'REST API Checker';
    }

    /**
     * @inheritDoc
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        // API monitors need a target (URL)
        if (empty($monitor->target)) {
            // Check configuration for URL
            $config = $monitor->getConfiguration();
            if (empty($config['url'])) {
                Log::warning("REST API monitor {$monitor->id} has no URL configured");

                return false;
            }
        }

        if (empty($monitor->timeout) || $monitor->timeout <= 0) {
            Log::warning("Monitor {$monitor->id} has invalid timeout");

            return false;
        }

        return true;
    }

    /**
     * Execute the REST API check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $config = $monitor->getConfiguration();
        $startTime = microtime(true);

        try {
            // Build adapter config from monitor configuration
            $adapterConfig = $this->buildAdapterConfig($monitor, $config);

            $adapter = new RestApiAdapter($adapterConfig);
            $adapter->connect();

            // Make the request
            $url = $config['url'] ?? $monitor->target ?? '';
            $method = strtoupper($config['method'] ?? 'GET');

            $client = $adapter->getClient();
            if ($client === null) {
                return $this->buildErrorResult('REST API client failed to initialize', 0);
            }

            $response = $client->request($method, $url);
            $responseTime = $this->calculateResponseTime($startTime);
            $statusCode = $response->getStatusCode();

            // Run validations
            $validationErrors = [];

            // Status code validation
            $expectedStatus = (int)($config['status_code'] ?? 200);
            if ($statusCode !== $expectedStatus) {
                $validationErrors[] = "Status code {$statusCode} (expected {$expectedStatus})";
            }

            // JSON path validation
            if (!empty($config['json_path'])) {
                $json = $response->getJson();
                if (is_array($json)) {
                    $actualValue = $adapter->resolveJsonPath($json, $config['json_path']);
                    $expectedValue = $config['expected_value'] ?? null;

                    if ($expectedValue !== null && (string)$actualValue !== (string)$expectedValue) {
                        $validationErrors[] = "JSON path '{$config['json_path']}': got '" .
                            ($actualValue ?? 'null') . "' (expected '{$expectedValue}')";
                    } elseif ($actualValue === null) {
                        $validationErrors[] = "JSON path '{$config['json_path']}' not found in response";
                    }
                } else {
                    $validationErrors[] = 'Response is not valid JSON';
                }
            }

            // Content contains validation
            if (!empty($config['content_contains'])) {
                $body = $response->getStringBody();
                if (!str_contains($body, $config['content_contains'])) {
                    $validationErrors[] = "Response does not contain '{$config['content_contains']}'";
                }
            }

            $adapter->disconnect();

            // Build result based on validations
            if (!empty($validationErrors)) {
                $errorMsg = implode('; ', $validationErrors);

                return $this->buildErrorResult(
                    $errorMsg,
                    $responseTime,
                    [
                        'status_code' => $statusCode,
                        'url' => $url,
                        'validations_failed' => $validationErrors,
                    ]
                );
            }

            // Check for degraded performance
            if ($this->isDegraded($monitor, $responseTime)) {
                return $this->buildDegradedResult(
                    $responseTime,
                    "High response time ({$responseTime}ms)",
                    $statusCode,
                    ['url' => $url]
                );
            }

            return $this->buildSuccessResult(
                $responseTime,
                $statusCode,
                [
                    'url' => $url,
                    'content_type' => $response->getHeaderLine('Content-Type'),
                ]
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("REST API check failed for monitor {$monitor->name}: {$e->getMessage()}");

            return $this->buildErrorResult(
                $e->getMessage(),
                $responseTime,
                [
                    'url' => $config['url'] ?? $monitor->target ?? '',
                    'exception_type' => get_class($e),
                ]
            );
        }
    }

    /**
     * Build adapter configuration from monitor config
     *
     * @param \App\Model\Entity\Monitor $monitor Monitor entity
     * @param array<string, mixed> $config Monitor configuration
     * @return array<string, mixed> Adapter configuration
     */
    protected function buildAdapterConfig(Monitor $monitor, array $config): array
    {
        $adapterConfig = [
            'base_url' => $config['url'] ?? $monitor->target ?? '',
            'method' => $config['method'] ?? 'GET',
            'timeout' => $monitor->timeout ?? 30,
            'headers' => $config['headers'] ?? [],
        ];

        // Authentication settings
        if (!empty($config['auth_type'])) {
            $adapterConfig['auth_type'] = $config['auth_type'];
            $adapterConfig['api_key'] = $config['api_key'] ?? '';
            $adapterConfig['username'] = $config['username'] ?? '';
            $adapterConfig['password'] = $config['password'] ?? '';
            $adapterConfig['api_key_header'] = $config['api_key_header'] ?? 'X-API-Key';
        }

        // If integration_id is set, load integration config
        if (!empty($config['integration_id'])) {
            $adapterConfig = $this->mergeIntegrationConfig(
                (int)$config['integration_id'],
                $adapterConfig
            );
        }

        return $adapterConfig;
    }

    /**
     * Merge integration configuration into adapter config
     *
     * @param int $integrationId Integration ID
     * @param array<string, mixed> $adapterConfig Current adapter config
     * @return array<string, mixed> Merged configuration
     */
    protected function mergeIntegrationConfig(int $integrationId, array $adapterConfig): array
    {
        try {
            $integrationsTable = TableRegistry::getTableLocator()->get('Integrations');
            $integration = $integrationsTable->get($integrationId);
            $integrationConfig = $integration->getConfiguration();

            // Merge base_url from integration if not set in monitor
            if (empty($adapterConfig['base_url']) && !empty($integrationConfig['base_url'])) {
                $adapterConfig['base_url'] = $integrationConfig['base_url'];
            }

            // Merge auth settings from integration
            if (empty($adapterConfig['auth_type']) && !empty($integrationConfig['auth_type'])) {
                $adapterConfig['auth_type'] = $integrationConfig['auth_type'];
                $adapterConfig['api_key'] = $integrationConfig['api_key'] ?? '';
                $adapterConfig['username'] = $integrationConfig['username'] ?? '';
                $adapterConfig['password'] = $integrationConfig['password'] ?? '';
            }

            // Merge headers
            if (!empty($integrationConfig['headers'])) {
                $integrationHeaders = is_string($integrationConfig['headers'])
                    ? json_decode($integrationConfig['headers'], true)
                    : $integrationConfig['headers'];

                if (is_array($integrationHeaders)) {
                    $adapterConfig['headers'] = array_merge(
                        $integrationHeaders,
                        $adapterConfig['headers']
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to load integration {$integrationId}: {$e->getMessage()}");
        }

        return $adapterConfig;
    }
}
