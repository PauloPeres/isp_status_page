<?php
declare(strict_types=1);

namespace App\Integration;

/**
 * IntegrationInterface
 *
 * Base interface for all external API integrations.
 * All adapters (IXC, Zabbix, REST API) must implement this interface.
 *
 * @see docs/API_INTEGRATIONS.md for integration specifications
 */
interface IntegrationInterface
{
    /**
     * Establish connection with the external service
     *
     * This method should handle authentication and prepare the client
     * for subsequent API calls.
     *
     * @return bool True if connection successful, false otherwise
     */
    public function connect(): bool;

    /**
     * Test the connection to the external service
     *
     * This method should verify that the integration is properly configured
     * and can communicate with the external service.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     response_time?: float,
     *     version?: string,
     *     error?: string
     * }
     */
    public function testConnection(): array;

    /**
     * Get status of a specific resource
     *
     * Retrieves the current status of a monitored resource (equipment, host, service, etc.)
     *
     * @param string $resourceId Unique identifier of the resource in the external system
     * @return array{
     *     status: string,
     *     online: bool,
     *     message?: string,
     *     last_seen?: string,
     *     metadata?: array
     * }
     */
    public function getStatus(string $resourceId): array;

    /**
     * Get metrics for a specific resource
     *
     * Retrieves performance metrics, statistics, or detailed information
     * about the monitored resource.
     *
     * @param string $resourceId Unique identifier of the resource
     * @param array<string, mixed> $params Optional parameters for filtering/customizing the request
     * @return array{
     *     metrics: array,
     *     timestamp: string,
     *     resource_id: string,
     *     metadata?: array
     * }
     */
    public function getMetrics(string $resourceId, array $params = []): array;

    /**
     * Disconnect from the external service
     *
     * Clean up resources, close connections, invalidate tokens, etc.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Get integration name
     *
     * Returns a human-readable name for this integration
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get integration type
     *
     * Returns the integration type identifier (ixc, zabbix, rest_api, etc.)
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Check if currently connected
     *
     * @return bool
     */
    public function isConnected(): bool;
}
