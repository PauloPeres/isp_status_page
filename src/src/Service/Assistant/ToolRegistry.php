<?php
declare(strict_types=1);

namespace App\Service\Assistant;

/**
 * ToolRegistry
 *
 * Defines all available tools with their JSON schema for Claude's tool_use.
 * Tools are organized by category and filtered by user role.
 */
class ToolRegistry
{
    /**
     * Roles that can execute write operations.
     *
     * @var array<string>
     */
    private const WRITE_ROLES = ['owner', 'admin'];

    /**
     * Get all tool definitions available for a given role.
     *
     * @param string $role The user's role within the organization.
     * @return array<array> Array of tool definitions in Claude's tool_use format.
     */
    public function getToolsForRole(string $role): array
    {
        $tools = $this->getReadTools();

        if (in_array($role, self::WRITE_ROLES, true)) {
            $tools = array_merge($tools, $this->getWriteTools());
        }

        return $tools;
    }

    /**
     * Get all tool names available for a given role.
     *
     * @param string $role The user's role.
     * @return array<string>
     */
    public function getToolNamesForRole(string $role): array
    {
        return array_column($this->getToolsForRole($role), 'name');
    }

    /**
     * Check if a tool name is a write operation.
     *
     * @param string $toolName The tool name.
     * @return bool
     */
    public function isWriteTool(string $toolName): bool
    {
        $writeToolNames = array_column($this->getWriteTools(), 'name');

        return in_array($toolName, $writeToolNames, true);
    }

    /**
     * Get read-only tool definitions.
     *
     * @return array<array>
     */
    private function getReadTools(): array
    {
        return [
            [
                'name' => 'list_monitors',
                'description' => 'List all monitors with their current status. Can filter by status (up/down/degraded), type, or search by name.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['up', 'down', 'degraded', 'unknown'],
                            'description' => 'Filter by monitor status.',
                        ],
                        'type' => [
                            'type' => 'string',
                            'description' => 'Filter by monitor type (http, ping, port, api, heartbeat, keyword, ssl, ixc, zabbix).',
                        ],
                        'search' => [
                            'type' => 'string',
                            'description' => 'Search monitors by name (partial match).',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'get_monitor',
                'description' => 'Get detailed information about a specific monitor by its ID or public_id, including configuration, last check time, and uptime percentage.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'monitor_id' => [
                            'type' => 'string',
                            'description' => 'The monitor ID (integer) or public_id (UUID).',
                        ],
                    ],
                    'required' => ['monitor_id'],
                ],
            ],
            [
                'name' => 'get_dashboard_summary',
                'description' => 'Get a summary of the monitoring dashboard including total monitors, status counts, active incidents, and recent activity.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
            ],
            [
                'name' => 'list_incidents',
                'description' => 'List incidents. Can filter by status (investigating/identified/monitoring/resolved) and optionally limit results.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['investigating', 'identified', 'monitoring', 'resolved'],
                            'description' => 'Filter by incident status.',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of incidents to return (default: 20).',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'list_notification_channels',
                'description' => 'List all configured notification channels (email, Slack, Discord, Telegram, SMS, WhatsApp, voice call, webhooks).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'description' => 'Filter by channel type.',
                        ],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name' => 'list_escalation_policies',
                'description' => 'List all escalation policies with their steps and notification channels.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                ],
            ],
            [
                'name' => 'get_sla_report',
                'description' => 'Get SLA compliance report for monitors. Shows uptime percentages and compliance status.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'monitor_id' => [
                            'type' => 'string',
                            'description' => 'Specific monitor ID to get SLA for. If omitted, returns summary for all monitors.',
                        ],
                        'period' => [
                            'type' => 'string',
                            'enum' => ['monthly', 'quarterly', 'yearly'],
                            'description' => 'SLA measurement period (default: monthly).',
                        ],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Get write tool definitions (require owner/admin role).
     *
     * @return array<array>
     */
    private function getWriteTools(): array
    {
        return [
            [
                'name' => 'create_monitor',
                'description' => 'Create a new monitor. Requires at least a name, type, and target URL/host. Returns the created monitor.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Monitor display name.',
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['http', 'ping', 'port', 'api', 'heartbeat', 'keyword', 'ssl'],
                            'description' => 'Monitor type.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'description' => 'Target URL for HTTP/API/keyword/SSL monitors.',
                        ],
                        'host' => [
                            'type' => 'string',
                            'description' => 'Target host for ping/port monitors.',
                        ],
                        'port' => [
                            'type' => 'integer',
                            'description' => 'Target port for port monitors.',
                        ],
                        'check_interval' => [
                            'type' => 'integer',
                            'description' => 'Check interval in seconds (default: 300).',
                        ],
                        'timeout' => [
                            'type' => 'integer',
                            'description' => 'Timeout in seconds (default: 30).',
                        ],
                        'keyword' => [
                            'type' => 'string',
                            'description' => 'Keyword to search for in response (for keyword monitors).',
                        ],
                    ],
                    'required' => ['name', 'type'],
                ],
            ],
            [
                'name' => 'create_notification_channel',
                'description' => 'Create a new notification channel for receiving alerts.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Channel display name.',
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['email', 'slack', 'discord', 'telegram', 'sms', 'whatsapp', 'voice_call', 'webhook'],
                            'description' => 'Notification channel type.',
                        ],
                        'configuration' => [
                            'type' => 'object',
                            'description' => 'Channel-specific configuration (e.g., email address, webhook URL, Slack channel).',
                        ],
                    ],
                    'required' => ['name', 'type', 'configuration'],
                ],
            ],
            [
                'name' => 'create_escalation_policy',
                'description' => 'Create a new escalation policy with notification steps.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Policy name.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Policy description.',
                        ],
                        'steps' => [
                            'type' => 'array',
                            'description' => 'Escalation steps. Each step has delay_minutes and notification_channel_ids.',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'delay_minutes' => ['type' => 'integer'],
                                    'notification_channel_ids' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'pause_monitor',
                'description' => 'Pause a monitor so it stops being checked. The monitor will not generate alerts while paused.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'monitor_id' => [
                            'type' => 'string',
                            'description' => 'The monitor ID (integer) or public_id (UUID) to pause.',
                        ],
                    ],
                    'required' => ['monitor_id'],
                ],
            ],
            [
                'name' => 'resume_monitor',
                'description' => 'Resume a paused monitor so it starts being checked again.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'monitor_id' => [
                            'type' => 'string',
                            'description' => 'The monitor ID (integer) or public_id (UUID) to resume.',
                        ],
                    ],
                    'required' => ['monitor_id'],
                ],
            ],
            [
                'name' => 'acknowledge_incident',
                'description' => 'Acknowledge an active incident. This signals that someone is working on it and can suppress further escalation alerts.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'incident_id' => [
                            'type' => 'string',
                            'description' => 'The incident ID (integer) or public_id (UUID) to acknowledge.',
                        ],
                    ],
                    'required' => ['incident_id'],
                ],
            ],
        ];
    }
}
