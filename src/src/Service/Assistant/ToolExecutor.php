<?php
declare(strict_types=1);

namespace App\Service\Assistant;

use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * ToolExecutor
 *
 * Executes tools by calling CakePHP Table/Service methods directly.
 * Returns clean arrays (not entities) to avoid serialization issues.
 */
class ToolExecutor
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Tool registry for role checking.
     *
     * @var \App\Service\Assistant\ToolRegistry
     */
    private ToolRegistry $toolRegistry;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->toolRegistry = new ToolRegistry();
    }

    /**
     * Execute a tool by name with the given arguments.
     *
     * @param string $toolName The tool to execute.
     * @param array $args The tool arguments.
     * @param int $orgId The organization ID for scoping.
     * @param int $userId The user ID for attribution.
     * @param string $role The user's role.
     * @return array The tool result as a clean array.
     */
    public function execute(string $toolName, array $args, int $orgId, int $userId, string $role): array
    {
        // Check write permission
        if ($this->toolRegistry->isWriteTool($toolName) && !in_array($role, ['owner', 'admin'], true)) {
            return ['error' => 'Insufficient permissions. Write operations require owner or admin role.'];
        }

        try {
            return match ($toolName) {
                'list_monitors' => $this->listMonitors($args, $orgId),
                'get_monitor' => $this->getMonitor($args, $orgId),
                'get_dashboard_summary' => $this->getDashboardSummary($orgId),
                'list_incidents' => $this->listIncidents($args, $orgId),
                'list_notification_channels' => $this->listNotificationChannels($args, $orgId),
                'list_escalation_policies' => $this->listEscalationPolicies($orgId),
                'get_sla_report' => $this->getSlaReport($args, $orgId),
                'create_monitor' => $this->createMonitor($args, $orgId),
                'create_notification_channel' => $this->createNotificationChannel($args, $orgId),
                'create_escalation_policy' => $this->createEscalationPolicy($args, $orgId),
                'pause_monitor' => $this->pauseMonitor($args, $orgId),
                'resume_monitor' => $this->resumeMonitor($args, $orgId),
                'acknowledge_incident' => $this->acknowledgeIncident($args, $orgId, $userId),
                default => ['error' => "Unknown tool: {$toolName}"],
            };
        } catch (\Throwable $e) {
            $this->log("ToolExecutor error for {$toolName}: {$e->getMessage()}", 'error');

            return ['error' => "Tool execution failed: {$e->getMessage()}"];
        }
    }

    /**
     * List monitors with optional filters.
     */
    private function listMonitors(array $args, int $orgId): array
    {
        $query = $this->fetchTable('Monitors')->find()
            ->where(['Monitors.organization_id' => $orgId])
            ->orderBy(['Monitors.name' => 'ASC']);

        if (!empty($args['status'])) {
            $query->where(['Monitors.status' => $args['status']]);
        }
        if (!empty($args['type'])) {
            $query->where(['Monitors.type' => $args['type']]);
        }
        if (!empty($args['search'])) {
            $query->where(['Monitors.name ILIKE' => '%' . $args['search'] . '%']);
        }

        $monitors = $query->toArray();

        return [
            'count' => count($monitors),
            'monitors' => array_map(function ($m) {
                return [
                    'id' => $m->id,
                    'public_id' => $m->public_id ?? null,
                    'name' => $m->name,
                    'type' => $m->type,
                    'status' => $m->status,
                    'active' => $m->active,
                    'uptime_percentage' => $m->uptime_percentage,
                    'last_check_at' => $m->last_check_at ? $m->last_check_at->toIso8601String() : null,
                    'check_interval' => $m->check_interval,
                ];
            }, $monitors),
        ];
    }

    /**
     * Get a specific monitor by ID or public_id.
     */
    private function getMonitor(array $args, int $orgId): array
    {
        $monitor = $this->resolveMonitor($args['monitor_id'] ?? '', $orgId);
        if (!$monitor) {
            return ['error' => 'Monitor not found.'];
        }

        return [
            'id' => $monitor->id,
            'public_id' => $monitor->public_id ?? null,
            'name' => $monitor->name,
            'type' => $monitor->type,
            'status' => $monitor->status,
            'active' => $monitor->active,
            'configuration' => $monitor->getConfiguration(),
            'check_interval' => $monitor->check_interval,
            'timeout' => $monitor->timeout,
            'retry_count' => $monitor->retry_count,
            'uptime_percentage' => $monitor->uptime_percentage,
            'last_check_at' => $monitor->last_check_at ? $monitor->last_check_at->toIso8601String() : null,
            'next_check_at' => $monitor->next_check_at ? $monitor->next_check_at->toIso8601String() : null,
            'visible_on_status_page' => $monitor->visible_on_status_page,
            'tags' => $monitor->getTags(),
            'created' => $monitor->created ? $monitor->created->toIso8601String() : null,
        ];
    }

    /**
     * Get dashboard summary data.
     */
    private function getDashboardSummary(int $orgId): array
    {
        $monitorsTable = $this->fetchTable('Monitors');
        $incidentsTable = $this->fetchTable('Incidents');

        $monitors = $monitorsTable->find()
            ->where(['Monitors.organization_id' => $orgId])
            ->toArray();

        $total = count($monitors);
        $up = 0;
        $down = 0;
        $degraded = 0;
        $unknown = 0;
        $paused = 0;

        foreach ($monitors as $m) {
            if (!$m->active) {
                $paused++;
                continue;
            }
            match ($m->status) {
                'up' => $up++,
                'down' => $down++,
                'degraded' => $degraded++,
                default => $unknown++,
            };
        }

        $activeIncidents = $incidentsTable->find()
            ->where([
                'Incidents.organization_id' => $orgId,
                'Incidents.status !=' => 'resolved',
            ])
            ->count();

        return [
            'total_monitors' => $total,
            'up' => $up,
            'down' => $down,
            'degraded' => $degraded,
            'unknown' => $unknown,
            'paused' => $paused,
            'active_incidents' => $activeIncidents,
        ];
    }

    /**
     * List incidents with optional filters.
     */
    private function listIncidents(array $args, int $orgId): array
    {
        $query = $this->fetchTable('Incidents')->find()
            ->contain(['Monitors' => ['fields' => ['id', 'name', 'type']]])
            ->where(['Incidents.organization_id' => $orgId])
            ->orderBy(['Incidents.started_at' => 'DESC']);

        if (!empty($args['status'])) {
            $query->where(['Incidents.status' => $args['status']]);
        }

        $limit = (int)($args['limit'] ?? 20);
        $query->limit(min($limit, 50));

        $incidents = $query->toArray();

        return [
            'count' => count($incidents),
            'incidents' => array_map(function ($i) {
                return [
                    'id' => $i->id,
                    'public_id' => $i->public_id ?? null,
                    'title' => $i->title,
                    'status' => $i->status,
                    'severity' => $i->severity,
                    'monitor_name' => $i->monitor->name ?? 'Unknown',
                    'started_at' => $i->started_at ? $i->started_at->toIso8601String() : null,
                    'resolved_at' => $i->resolved_at ? $i->resolved_at->toIso8601String() : null,
                    'acknowledged_at' => $i->acknowledged_at ? $i->acknowledged_at->toIso8601String() : null,
                    'auto_created' => $i->auto_created,
                ];
            }, $incidents),
        ];
    }

    /**
     * List notification channels.
     */
    private function listNotificationChannels(array $args, int $orgId): array
    {
        $query = $this->fetchTable('NotificationChannels')->find()
            ->where(['NotificationChannels.organization_id' => $orgId])
            ->orderBy(['NotificationChannels.name' => 'ASC']);

        if (!empty($args['type'])) {
            $query->where(['NotificationChannels.type' => $args['type']]);
        }

        $channels = $query->toArray();

        return [
            'count' => count($channels),
            'channels' => array_map(function ($c) {
                return [
                    'id' => $c->id,
                    'public_id' => $c->public_id ?? null,
                    'name' => $c->name,
                    'type' => $c->type,
                    'active' => $c->active ?? true,
                ];
            }, $channels),
        ];
    }

    /**
     * List escalation policies.
     */
    private function listEscalationPolicies(int $orgId): array
    {
        $policies = $this->fetchTable('EscalationPolicies')->find()
            ->contain(['EscalationSteps'])
            ->where(['EscalationPolicies.organization_id' => $orgId])
            ->orderBy(['EscalationPolicies.name' => 'ASC'])
            ->toArray();

        return [
            'count' => count($policies),
            'policies' => array_map(function ($p) {
                $steps = [];
                if (!empty($p->escalation_steps)) {
                    foreach ($p->escalation_steps as $step) {
                        $steps[] = [
                            'step_order' => $step->step_order ?? null,
                            'delay_minutes' => $step->delay_minutes ?? null,
                            'notification_channel_id' => $step->notification_channel_id ?? null,
                        ];
                    }
                }

                return [
                    'id' => $p->id,
                    'public_id' => $p->public_id ?? null,
                    'name' => $p->name,
                    'description' => $p->description ?? null,
                    'steps' => $steps,
                ];
            }, $policies),
        ];
    }

    /**
     * Get SLA report.
     */
    private function getSlaReport(array $args, int $orgId): array
    {
        $period = $args['period'] ?? 'monthly';

        try {
            $slaService = new \App\Service\SlaService();

            if (!empty($args['monitor_id'])) {
                $monitor = $this->resolveMonitor($args['monitor_id'], $orgId);
                if (!$monitor) {
                    return ['error' => 'Monitor not found.'];
                }

                $result = $slaService->calculateCurrentSla($monitor->id, $period);

                return [
                    'monitor' => $monitor->name,
                    'period' => $period,
                    'sla' => $result,
                ];
            }

            // Summary for all monitors
            $monitors = $this->fetchTable('Monitors')->find()
                ->where(['Monitors.organization_id' => $orgId, 'Monitors.active' => true])
                ->toArray();

            $results = [];
            foreach ($monitors as $monitor) {
                try {
                    $sla = $slaService->calculateCurrentSla($monitor->id, $period);
                    $results[] = [
                        'monitor_name' => $monitor->name,
                        'uptime_percentage' => $sla['uptime_percentage'] ?? null,
                        'status' => $sla['status'] ?? 'unknown',
                    ];
                } catch (\Throwable $e) {
                    $results[] = [
                        'monitor_name' => $monitor->name,
                        'error' => 'Could not calculate SLA.',
                    ];
                }
            }

            return [
                'period' => $period,
                'monitor_count' => count($results),
                'reports' => $results,
            ];
        } catch (\Throwable $e) {
            return ['error' => 'SLA service unavailable: ' . $e->getMessage()];
        }
    }

    /**
     * Create a new monitor.
     */
    private function createMonitor(array $args, int $orgId): array
    {
        $monitorsTable = $this->fetchTable('Monitors');

        $configuration = [];
        $type = $args['type'] ?? 'http';

        switch ($type) {
            case 'http':
            case 'api':
            case 'keyword':
            case 'ssl':
                if (empty($args['url'])) {
                    return ['error' => 'URL is required for ' . $type . ' monitors.'];
                }
                $configuration['url'] = $args['url'];
                if ($type === 'keyword' && !empty($args['keyword'])) {
                    $configuration['keyword'] = $args['keyword'];
                }
                break;
            case 'ping':
                if (empty($args['host'])) {
                    return ['error' => 'Host is required for ping monitors.'];
                }
                $configuration['host'] = $args['host'];
                break;
            case 'port':
                if (empty($args['host']) || empty($args['port'])) {
                    return ['error' => 'Host and port are required for port monitors.'];
                }
                $configuration['host'] = $args['host'];
                $configuration['port'] = (int)$args['port'];
                break;
        }

        $data = [
            'organization_id' => $orgId,
            'name' => $args['name'] ?? 'New Monitor',
            'type' => $type,
            'configuration' => json_encode($configuration),
            'check_interval' => (int)($args['check_interval'] ?? 300),
            'timeout' => (int)($args['timeout'] ?? 30),
            'retry_count' => 3,
            'status' => 'unknown',
            'active' => true,
            'visible_on_status_page' => true,
            'display_order' => 0,
        ];

        $monitor = $monitorsTable->newEntity($data);
        if ($monitorsTable->save($monitor)) {
            return [
                'success' => true,
                'message' => "Monitor '{$monitor->name}' created successfully.",
                'monitor' => [
                    'id' => $monitor->id,
                    'public_id' => $monitor->public_id ?? null,
                    'name' => $monitor->name,
                    'type' => $monitor->type,
                    'status' => $monitor->status,
                ],
            ];
        }

        return ['error' => 'Failed to create monitor.', 'validation_errors' => $monitor->getErrors()];
    }

    /**
     * Create a notification channel.
     */
    private function createNotificationChannel(array $args, int $orgId): array
    {
        $table = $this->fetchTable('NotificationChannels');

        $data = [
            'organization_id' => $orgId,
            'name' => $args['name'] ?? 'New Channel',
            'type' => $args['type'] ?? 'email',
            'configuration' => json_encode($args['configuration'] ?? []),
            'active' => true,
        ];

        $channel = $table->newEntity($data);
        if ($table->save($channel)) {
            return [
                'success' => true,
                'message' => "Notification channel '{$channel->name}' created successfully.",
                'channel' => [
                    'id' => $channel->id,
                    'public_id' => $channel->public_id ?? null,
                    'name' => $channel->name,
                    'type' => $channel->type,
                ],
            ];
        }

        return ['error' => 'Failed to create notification channel.', 'validation_errors' => $channel->getErrors()];
    }

    /**
     * Create an escalation policy.
     */
    private function createEscalationPolicy(array $args, int $orgId): array
    {
        $table = $this->fetchTable('EscalationPolicies');

        $data = [
            'organization_id' => $orgId,
            'name' => $args['name'] ?? 'New Policy',
            'description' => $args['description'] ?? null,
            'active' => true,
        ];

        $policy = $table->newEntity($data);
        if ($table->save($policy)) {
            // Create steps if provided
            if (!empty($args['steps'])) {
                $stepsTable = $this->fetchTable('EscalationSteps');
                foreach ($args['steps'] as $order => $stepData) {
                    $step = $stepsTable->newEntity([
                        'escalation_policy_id' => $policy->id,
                        'step_order' => $order + 1,
                        'delay_minutes' => (int)($stepData['delay_minutes'] ?? 0),
                        'notification_channel_id' => $stepData['notification_channel_ids'][0] ?? null,
                    ]);
                    $stepsTable->save($step);
                }
            }

            return [
                'success' => true,
                'message' => "Escalation policy '{$policy->name}' created successfully.",
                'policy' => [
                    'id' => $policy->id,
                    'public_id' => $policy->public_id ?? null,
                    'name' => $policy->name,
                ],
            ];
        }

        return ['error' => 'Failed to create escalation policy.', 'validation_errors' => $policy->getErrors()];
    }

    /**
     * Pause a monitor.
     */
    private function pauseMonitor(array $args, int $orgId): array
    {
        $monitor = $this->resolveMonitor($args['monitor_id'] ?? '', $orgId);
        if (!$monitor) {
            return ['error' => 'Monitor not found.'];
        }

        if (!$monitor->active) {
            return ['message' => "Monitor '{$monitor->name}' is already paused."];
        }

        $monitor->active = false;
        $table = $this->fetchTable('Monitors');
        if ($table->save($monitor)) {
            return [
                'success' => true,
                'message' => "Monitor '{$monitor->name}' has been paused.",
            ];
        }

        return ['error' => 'Failed to pause monitor.'];
    }

    /**
     * Resume a monitor.
     */
    private function resumeMonitor(array $args, int $orgId): array
    {
        $monitor = $this->resolveMonitor($args['monitor_id'] ?? '', $orgId);
        if (!$monitor) {
            return ['error' => 'Monitor not found.'];
        }

        if ($monitor->active) {
            return ['message' => "Monitor '{$monitor->name}' is already active."];
        }

        $monitor->active = true;
        $table = $this->fetchTable('Monitors');
        if ($table->save($monitor)) {
            return [
                'success' => true,
                'message' => "Monitor '{$monitor->name}' has been resumed.",
            ];
        }

        return ['error' => 'Failed to resume monitor.'];
    }

    /**
     * Acknowledge an incident.
     */
    private function acknowledgeIncident(array $args, int $orgId, int $userId): array
    {
        $incidentId = $args['incident_id'] ?? '';
        $table = $this->fetchTable('Incidents');

        $incident = $this->resolveEntityById($table, $incidentId, $orgId);
        if (!$incident) {
            return ['error' => 'Incident not found.'];
        }

        if ($incident->acknowledged_at) {
            return ['message' => 'Incident has already been acknowledged.'];
        }

        if ($incident->status === 'resolved') {
            return ['message' => 'Incident is already resolved.'];
        }

        $incident->acknowledged_by_user_id = $userId;
        $incident->acknowledged_at = new DateTime();
        $incident->acknowledged_via = 'ai_chat';

        if ($table->save($incident)) {
            return [
                'success' => true,
                'message' => "Incident '{$incident->title}' has been acknowledged.",
            ];
        }

        return ['error' => 'Failed to acknowledge incident.'];
    }

    /**
     * Resolve a monitor by integer ID or UUID public_id.
     *
     * @param string $id The ID or public_id.
     * @param int $orgId The organization ID.
     * @return \App\Model\Entity\Monitor|null
     */
    private function resolveMonitor(string $id, int $orgId): ?\App\Model\Entity\Monitor
    {
        $table = $this->fetchTable('Monitors');

        return $this->resolveEntityById($table, $id, $orgId);
    }

    /**
     * Resolve an entity by integer ID or UUID public_id, scoped to an organization.
     *
     * @param \Cake\ORM\Table $table The table instance.
     * @param string $id The ID or public_id.
     * @param int $orgId The organization ID.
     * @return \Cake\Datasource\EntityInterface|null
     */
    private function resolveEntityById(\Cake\ORM\Table $table, string $id, int $orgId): ?\Cake\Datasource\EntityInterface
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-/i', $id)) {
            return $table->find('byPublicId', publicId: $id)
                ->where([$table->getAlias() . '.organization_id' => $orgId])
                ->first();
        }

        try {
            $entity = $table->get((int)$id);
            if ($entity->organization_id === $orgId) {
                return $entity;
            }

            return null;
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            return null;
        }
    }
}
