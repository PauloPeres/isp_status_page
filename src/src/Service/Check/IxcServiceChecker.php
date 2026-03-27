<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Integration\Ixc\IxcAdapter;
use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * IXC Service Checker
 *
 * Checks the status of IXC services by querying the IXC API
 * for service connection and status information.
 *
 * Monitor type: 'ixc_service'
 */
class IxcServiceChecker extends AbstractChecker
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
        if (empty($config['service_id'])) {
            return $this->buildErrorResult('No service_id configured for IXC service monitor');
        }

        return parent::check($monitor);
    }

    /**
     * Execute the IXC service check
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            $config = $monitor->getConfiguration();
            $serviceId = $config['service_id'] ?? null;

            if (empty($serviceId)) {
                return $this->buildErrorResult('No service_id configured for IXC service monitor');
            }

            // Get or create adapter
            $adapter = $this->getAdapter($config);

            // Query service status
            $status = $adapter->getStatus($serviceId);
            $responseTime = $this->calculateResponseTime($startTime);

            // Determine check result based on status
            if ($status['status'] === 'up' && $status['online']) {
                // Check for degradation
                if ($this->isDegraded($monitor, $responseTime)) {
                    return $this->buildDegradedResult(
                        $responseTime,
                        'IXC service response time is high',
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
                    $status['message'] ?? 'IXC service is degraded',
                    null,
                    $status['metadata'] ?? []
                );
            }

            return $this->buildErrorResult(
                $status['message'] ?? 'IXC service is not available',
                $responseTime,
                $status['metadata'] ?? []
            );
        } catch (\Exception $e) {
            $responseTime = $this->calculateResponseTime($startTime);

            Log::error("IXC service check failed: {$e->getMessage()}", [
                'monitor_id' => $monitor->id,
            ]);

            return $this->buildErrorResult(
                'IXC API error: ' . $e->getMessage(),
                $responseTime
            );
        }
    }

    /**
     * Validate monitor configuration for IXC service checks
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        $config = $monitor->getConfiguration();

        // Must have integration_id or connection settings
        if (empty($config['integration_id']) && empty($config['base_url'])) {
            Log::warning("Monitor {$monitor->id} missing integration_id or base_url for IXC service check");

            return false;
        }

        // Must have service_id
        if (empty($config['service_id'])) {
            Log::warning("Monitor {$monitor->id} missing service_id for IXC service check");

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
        return 'ixc_service';
    }

    /**
     * Get human-readable checker name
     *
     * @return string
     */
    public function getName(): string
    {
        return 'IXC Service Checker';
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
