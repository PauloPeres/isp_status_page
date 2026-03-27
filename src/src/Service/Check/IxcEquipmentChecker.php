<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Integration\Ixc\IxcAdapter;
use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * IXC Equipment Checker
 *
 * Checks the status of IXC network equipment (OLT, ONU, routers, etc.)
 * by querying the IXC API for equipment status and metrics.
 *
 * Monitor type: 'ixc_equipment'
 */
class IxcEquipmentChecker extends AbstractChecker
{
    /**
     * IXC Adapter instance
     *
     * @var \App\Integration\Ixc\IxcAdapter|null
     */
    protected ?IxcAdapter $adapter;

    /**
     * Constructor
     *
     * @param \App\Integration\Ixc\IxcAdapter|null $adapter Optional adapter (for testing)
     */
    public function __construct(?IxcAdapter $adapter = null)
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
        if (empty($config['equipment_id'])) {
            return $this->buildErrorResult('No equipment_id configured for IXC equipment monitor');
        }

        return parent::check($monitor);
    }

    /**
     * Execute the IXC equipment check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            $config = $monitor->getConfiguration();
            $equipmentId = $config['equipment_id'] ?? null;

            if (empty($equipmentId)) {
                return $this->buildErrorResult('No equipment_id configured for IXC equipment monitor');
            }

            // Get or create adapter
            $adapter = $this->getAdapter($config);

            // Query equipment status
            $status = $adapter->getEquipmentStatus($equipmentId);
            $responseTime = $this->calculateResponseTime($startTime);

            // Check against configured thresholds
            $thresholds = $config['thresholds'] ?? [];

            // Determine check result based on status
            if ($status['status'] === 'up' && $status['online']) {
                // Check if any metrics exceed thresholds
                $thresholdResult = $this->checkThresholds($status['metadata'] ?? [], $thresholds);

                if ($thresholdResult !== null) {
                    return $this->buildDegradedResult(
                        $responseTime,
                        $thresholdResult,
                        null,
                        $status['metadata'] ?? []
                    );
                }

                // Check for slow response degradation
                if ($this->isDegraded($monitor, $responseTime)) {
                    return $this->buildDegradedResult(
                        $responseTime,
                        'IXC equipment response time is high',
                        null,
                        $status['metadata'] ?? []
                    );
                }

                return $this->buildSuccessResult(
                    $responseTime,
                    null,
                    $status['metadata'] ?? []
                );
            }

            if ($status['status'] === 'degraded') {
                return $this->buildDegradedResult(
                    $responseTime,
                    $status['message'] ?? 'IXC equipment is degraded',
                    null,
                    $status['metadata'] ?? []
                );
            }

            return $this->buildErrorResult(
                $status['message'] ?? 'IXC equipment is offline',
                $responseTime,
                $status['metadata'] ?? []
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("IXC equipment check failed: {$e->getMessage()}", [
                'monitor_id' => $monitor->id,
            ]);

            return $this->buildErrorResult(
                'IXC API error: ' . $e->getMessage(),
                $responseTime
            );
        }
    }

    /**
     * Check equipment metrics against configured thresholds
     *
     * @param array<string, mixed> $metadata Equipment metadata
     * @param array<string, int|float> $thresholds Threshold configuration
     * @return string|null Degradation message, or null if all within thresholds
     */
    protected function checkThresholds(array $metadata, array $thresholds): ?string
    {
        if (empty($thresholds)) {
            return null;
        }

        if (isset($thresholds['cpu_usage'], $metadata['cpu_usage'])) {
            if ($metadata['cpu_usage'] >= $thresholds['cpu_usage']) {
                return "CPU usage ({$metadata['cpu_usage']}%) exceeds threshold ({$thresholds['cpu_usage']}%)";
            }
        }

        if (isset($thresholds['memory_usage'], $metadata['memory_usage'])) {
            if ($metadata['memory_usage'] >= $thresholds['memory_usage']) {
                return "Memory usage ({$metadata['memory_usage']}%) exceeds threshold ({$thresholds['memory_usage']}%)";
            }
        }

        if (isset($thresholds['temperature'], $metadata['temperature'])) {
            if ($metadata['temperature'] >= $thresholds['temperature']) {
                return "Temperature ({$metadata['temperature']}C) exceeds threshold ({$thresholds['temperature']}C)";
            }
        }

        return null;
    }

    /**
     * Validate monitor configuration for IXC equipment checks
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        $config = $monitor->getConfiguration();

        // Must have integration_id or connection settings
        if (empty($config['integration_id']) && empty($config['base_url'])) {
            Log::warning("Monitor {$monitor->id} missing integration_id or base_url for IXC equipment check");

            return false;
        }

        // Must have equipment_id
        if (empty($config['equipment_id'])) {
            Log::warning("Monitor {$monitor->id} missing equipment_id for IXC equipment check");

            return false;
        }

        // Must have timeout
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
        return 'ixc_equipment';
    }

    /**
     * Get human-readable checker name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'IXC Equipment Checker';
    }

    /**
     * Get or create the IXC adapter
     *
     * @param array<string, mixed> $config Monitor configuration
     * @return \App\Integration\Ixc\IxcAdapter
     */
    protected function getAdapter(array $config): IxcAdapter
    {
        if ($this->adapter !== null) {
            return $this->adapter;
        }

        $adapterConfig = [
            'base_url' => $config['base_url'] ?? '',
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'timeout' => $config['timeout'] ?? 30,
        ];

        $this->adapter = new IxcAdapter($adapterConfig);

        return $this->adapter;
    }

    /**
     * Set the adapter (primarily for testing)
     *
     * @param \App\Integration\Ixc\IxcAdapter $adapter IXC adapter
     * @return void
     */
    public function setAdapter(IxcAdapter $adapter): void
    {
        $this->adapter = $adapter;
    }
}
