<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Integration\Zabbix\ZabbixAdapter;
use App\Integration\Zabbix\ZabbixMapper;
use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * Zabbix Host Checker
 *
 * Checks the availability of a Zabbix host by querying the host.get API.
 * Uses the 'available' field to determine if the host is reachable.
 *
 * Monitor configuration JSON:
 * {
 *     "integration_id": 2,
 *     "host_id": "10084"
 * }
 */
class ZabbixHostChecker extends AbstractChecker
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
     * Execute the check with specific error messages for missing fields
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    public function check(Monitor $monitor): array
    {
        $config = $monitor->getConfiguration();
        if (empty($config['host_id'])) {
            return $this->buildErrorResult('Missing host_id in monitor configuration');
        }

        return parent::check($monitor);
    }

    /**
     * Execute the Zabbix host availability check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            $config = $monitor->getConfiguration();
            $hostId = $config['host_id'] ?? null;

            if (empty($hostId)) {
                return $this->buildErrorResult(
                    'Missing host_id in monitor configuration',
                    0
                );
            }

            // Get host status from Zabbix
            $hostStatus = $this->adapter->getStatus($hostId);
            $responseTime = $this->calculateResponseTime($startTime);

            // Check for adapter errors
            if (isset($hostStatus['success']) && $hostStatus['success'] === false) {
                return $this->buildErrorResult(
                    $hostStatus['error'] ?? 'Failed to get host status',
                    $responseTime,
                    ['host_id' => $hostId]
                );
            }

            $available = (int)($hostStatus['available'] ?? ZabbixMapper::HOST_UNKNOWN);

            // Host available (1 = available)
            if ($available === ZabbixMapper::HOST_AVAILABLE) {
                // Check for degraded performance
                if ($this->isDegraded($monitor, $responseTime)) {
                    return $this->buildDegradedResult(
                        $responseTime,
                        "Host available but response time is high ({$responseTime}ms)",
                        null,
                        [
                            'host_id' => $hostId,
                            'host_name' => $hostStatus['host_name'] ?? '',
                            'available' => $available,
                        ]
                    );
                }

                return $this->buildSuccessResult(
                    $responseTime,
                    null,
                    [
                        'host_id' => $hostId,
                        'host_name' => $hostStatus['host_name'] ?? '',
                        'available' => $available,
                    ]
                );
            }

            // Host unavailable or unknown
            $message = $hostStatus['message'] ?? 'Host is not available';

            return $this->buildErrorResult(
                $message,
                $responseTime,
                [
                    'host_id' => $hostId,
                    'host_name' => $hostStatus['host_name'] ?? '',
                    'available' => $available,
                ]
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("Zabbix host check failed: {$e->getMessage()}", [
                'monitor_id' => $monitor->id,
            ]);

            return $this->buildErrorResult(
                $e->getMessage(),
                $responseTime
            );
        }
    }

    /**
     * Validate monitor configuration for Zabbix host checks
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        $config = $monitor->getConfiguration();

        if (empty($config['host_id'])) {
            Log::warning("Monitor {$monitor->id} has no host_id configured for Zabbix host check");

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
        return 'zabbix_host';
    }

    /**
     * Get human-readable checker name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Zabbix Host Checker';
    }
}
