<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Integration\Zabbix\ZabbixAdapter;
use App\Integration\Zabbix\ZabbixMapper;
use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * Zabbix Trigger Checker
 *
 * Checks the state of a Zabbix trigger by querying the trigger.get API.
 * Uses the 'value' field to determine trigger state (0=OK, 1=PROBLEM).
 *
 * Monitor configuration JSON:
 * {
 *     "integration_id": 2,
 *     "trigger_id": "13926"
 * }
 */
class ZabbixTriggerChecker extends AbstractChecker
{
    /**
     * Zabbix adapter instance
     *
     * @var \App\Integration\Zabbix\ZabbixAdapter
     */
    protected ZabbixAdapter $adapter;

    /**
     * Constructor
     *
     * @param \App\Integration\Zabbix\ZabbixAdapter $adapter Zabbix adapter instance
     */
    public function __construct(ZabbixAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Execute the Zabbix trigger state check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            $config = $monitor->getConfiguration();
            $triggerId = $config['trigger_id'] ?? null;

            if (empty($triggerId)) {
                return $this->buildErrorResult(
                    'Missing trigger_id in monitor configuration',
                    0
                );
            }

            // Get trigger status from Zabbix
            $triggerStatus = $this->adapter->getTrigger($triggerId);
            $responseTime = $this->calculateResponseTime($startTime);

            // Check for adapter errors
            if (isset($triggerStatus['success']) && $triggerStatus['success'] === false) {
                return $this->buildErrorResult(
                    $triggerStatus['error'] ?? 'Failed to get trigger status',
                    $responseTime,
                    ['trigger_id' => $triggerId]
                );
            }

            $value = (int)($triggerStatus['value'] ?? ZabbixMapper::TRIGGER_OK);

            // Trigger OK (0 = OK)
            if ($value === ZabbixMapper::TRIGGER_OK) {
                // Check for degraded performance
                if ($this->isDegraded($monitor, $responseTime)) {
                    return $this->buildDegradedResult(
                        $responseTime,
                        "Trigger OK but response time is high ({$responseTime}ms)",
                        null,
                        [
                            'trigger_id' => $triggerId,
                            'description' => $triggerStatus['description'] ?? '',
                            'value' => $value,
                            'severity' => $triggerStatus['severity'] ?? '',
                        ]
                    );
                }

                return $this->buildSuccessResult(
                    $responseTime,
                    null,
                    [
                        'trigger_id' => $triggerId,
                        'description' => $triggerStatus['description'] ?? '',
                        'value' => $value,
                        'severity' => $triggerStatus['severity'] ?? '',
                    ]
                );
            }

            // Trigger in PROBLEM state (1 = PROBLEM)
            $description = $triggerStatus['description'] ?? 'Unknown trigger';
            $severity = $triggerStatus['severity'] ?? 'unknown';
            $message = "Trigger PROBLEM: {$description} (severity: {$severity})";

            return $this->buildErrorResult(
                $message,
                $responseTime,
                [
                    'trigger_id' => $triggerId,
                    'description' => $description,
                    'value' => $value,
                    'severity' => $severity,
                    'priority' => $triggerStatus['priority'] ?? 0,
                    'last_change' => $triggerStatus['last_change'] ?? '',
                    'hosts' => $triggerStatus['hosts'] ?? [],
                ]
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("Zabbix trigger check failed: {$e->getMessage()}", [
                'monitor_id' => $monitor->id,
            ]);

            return $this->buildErrorResult(
                $e->getMessage(),
                $responseTime
            );
        }
    }

    /**
     * Validate monitor configuration for Zabbix trigger checks
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        $config = $monitor->getConfiguration();

        if (empty($config['trigger_id'])) {
            Log::warning("Monitor {$monitor->id} has no trigger_id configured for Zabbix trigger check");

            return false;
        }

        if (empty($monitor->timeout) || $monitor->timeout <= 0) {
            Log::warning("Monitor {$monitor->id} has invalid timeout");

            return false;
        }

        return true;
    }

    /**
     * Get checker type identifier
     *
     * @return string
     */
    public function getType(): string
    {
        return 'zabbix_trigger';
    }

    /**
     * Get human-readable checker name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Zabbix Trigger Checker';
    }
}
