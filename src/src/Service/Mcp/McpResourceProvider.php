<?php
declare(strict_types=1);

namespace App\Service\Mcp;

use App\Service\Assistant\ToolExecutor;
use Cake\Log\LogTrait;

/**
 * McpResourceProvider
 *
 * Provides resource data for MCP resource/list and resource/read methods.
 * Leverages the existing ToolExecutor to fetch data from the database.
 */
class McpResourceProvider
{
    use LogTrait;

    /**
     * @var \App\Service\Assistant\ToolExecutor
     */
    private ToolExecutor $toolExecutor;

    /**
     * Static resource definitions.
     *
     * @var array<array>
     */
    private const RESOURCES = [
        [
            'uri' => 'keepup://monitors',
            'name' => 'All Monitors',
            'description' => 'List of all monitors with current status',
            'mimeType' => 'application/json',
        ],
        [
            'uri' => 'keepup://incidents/active',
            'name' => 'Active Incidents',
            'description' => 'Currently active (unresolved) incidents',
            'mimeType' => 'application/json',
        ],
        [
            'uri' => 'keepup://dashboard',
            'name' => 'Dashboard Summary',
            'description' => 'Dashboard summary with monitor counts and active incident count',
            'mimeType' => 'application/json',
        ],
        [
            'uri' => 'keepup://channels',
            'name' => 'Notification Channels',
            'description' => 'All configured notification channels',
            'mimeType' => 'application/json',
        ],
        [
            'uri' => 'keepup://policies',
            'name' => 'Escalation Policies',
            'description' => 'All escalation policies with their steps',
            'mimeType' => 'application/json',
        ],
        [
            'uri' => 'keepup://sla',
            'name' => 'SLA Reports',
            'description' => 'SLA compliance reports for all monitors',
            'mimeType' => 'application/json',
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->toolExecutor = new ToolExecutor();
    }

    /**
     * List all available resources.
     *
     * @return array<array> Array of resource definitions.
     */
    public function listResources(): array
    {
        return self::RESOURCES;
    }

    /**
     * Read a resource by URI and return its contents.
     *
     * Supports both static resources (keepup://monitors, etc.) and
     * parameterized resources (keepup://monitors/{public_id}).
     *
     * @param string $uri The resource URI.
     * @param int $orgId The organization ID.
     * @return array{contents: array}|array{error: string} Resource data or error.
     */
    public function readResource(string $uri, int $orgId): array
    {
        try {
            $data = $this->fetchResourceData($uri, $orgId);

            return [
                'contents' => [
                    [
                        'uri' => $uri,
                        'mimeType' => 'application/json',
                        'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            $this->log("MCP resource read error for {$uri}: {$e->getMessage()}", 'error');

            return [
                'error' => "Failed to read resource: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Fetch the actual data for a resource URI.
     *
     * @param string $uri The resource URI.
     * @param int $orgId The organization ID.
     * @return array The resource data.
     * @throws \InvalidArgumentException If the URI is not recognized.
     */
    private function fetchResourceData(string $uri, int $orgId): array
    {
        // Check for parameterized monitor URI: keepup://monitors/{public_id}
        if (preg_match('#^keepup://monitors/(.+)$#', $uri, $matches)) {
            return $this->toolExecutor->execute('get_monitor', ['monitor_id' => $matches[1]], $orgId, 0, 'viewer');
        }

        return match ($uri) {
            'keepup://monitors' => $this->toolExecutor->execute('list_monitors', [], $orgId, 0, 'viewer'),
            'keepup://incidents/active' => $this->toolExecutor->execute('list_incidents', ['status' => 'investigating'], $orgId, 0, 'viewer'),
            'keepup://dashboard' => $this->toolExecutor->execute('get_dashboard_summary', [], $orgId, 0, 'viewer'),
            'keepup://channels' => $this->toolExecutor->execute('list_notification_channels', [], $orgId, 0, 'viewer'),
            'keepup://policies' => $this->toolExecutor->execute('list_escalation_policies', [], $orgId, 0, 'viewer'),
            'keepup://sla' => $this->toolExecutor->execute('get_sla_report', [], $orgId, 0, 'viewer'),
            default => throw new \InvalidArgumentException("Unknown resource URI: {$uri}"),
        };
    }
}
