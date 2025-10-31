<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\Log\Log;

/**
 * Abstract Checker
 *
 * Base class for all monitor checkers providing common functionality
 * and helper methods.
 */
abstract class AbstractChecker implements CheckerInterface
{
    /**
     * Status constants
     */
    public const STATUS_UP = 'up';
    public const STATUS_DOWN = 'down';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Execute the check with error handling and logging
     *
     * This method wraps the actual check implementation with common
     * error handling, logging, and timing functionality.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    public function check(Monitor $monitor): array
    {
        $startTime = microtime(true);

        try {
            // Validate configuration before checking
            if (!$this->validateConfiguration($monitor)) {
                return $this->buildErrorResult(
                    'Invalid monitor configuration',
                    0
                );
            }

            // Log check start
            Log::debug("Starting {$this->getType()} check for monitor: {$monitor->name}", [
                'monitor_id' => $monitor->id,
                'target' => $monitor->target,
            ]);

            // Execute the actual check (implemented by subclasses)
            $result = $this->executeCheck($monitor);

            // Calculate response time if not already set
            if (!isset($result['response_time'])) {
                $result['response_time'] = $this->calculateResponseTime($startTime);
            }

            // Log check completion
            Log::debug("Completed {$this->getType()} check for monitor: {$monitor->name}", [
                'monitor_id' => $monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time'],
            ]);

            return $result;
        } catch (\Exception $e) {
            // Log exception
            Log::error("Check failed for monitor: {$monitor->name}", [
                'monitor_id' => $monitor->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Calculate response time for failed check
            $responseTime = $this->calculateResponseTime($startTime);

            return $this->buildErrorResult($e->getMessage(), $responseTime);
        }
    }

    /**
     * Execute the actual check (to be implemented by subclasses)
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    abstract protected function executeCheck(Monitor $monitor): array;

    /**
     * Build a standardized success result
     *
     * @param int $responseTime Response time in milliseconds
     * @param int|null $statusCode Status code (HTTP code, port number, etc)
     * @param array $metadata Additional metadata
     * @return array Check result
     */
    protected function buildSuccessResult(
        int $responseTime,
        ?int $statusCode = null,
        array $metadata = []
    ): array {
        return [
            'status' => self::STATUS_UP,
            'response_time' => $responseTime,
            'status_code' => $statusCode,
            'error_message' => null,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build a standardized error result
     *
     * @param string $errorMessage Error message
     * @param int $responseTime Response time in milliseconds
     * @param array $metadata Additional metadata
     * @return array Check result
     */
    protected function buildErrorResult(
        string $errorMessage,
        int $responseTime = 0,
        array $metadata = []
    ): array {
        return [
            'status' => self::STATUS_DOWN,
            'response_time' => $responseTime,
            'status_code' => null,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ];
    }

    /**
     * Build a degraded result (service up but slow or partially failing)
     *
     * @param int $responseTime Response time in milliseconds
     * @param string $reason Reason for degradation
     * @param int|null $statusCode Status code
     * @param array $metadata Additional metadata
     * @return array Check result
     */
    protected function buildDegradedResult(
        int $responseTime,
        string $reason,
        ?int $statusCode = null,
        array $metadata = []
    ): array {
        return [
            'status' => self::STATUS_DEGRADED,
            'response_time' => $responseTime,
            'status_code' => $statusCode,
            'error_message' => $reason,
            'metadata' => $metadata,
        ];
    }

    /**
     * Calculate response time from start timestamp
     *
     * @param float $startTime Microtime start timestamp
     * @return int Response time in milliseconds
     */
    protected function calculateResponseTime(float $startTime): int
    {
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        return (int)round($duration);
    }

    /**
     * Check if response time indicates degraded service
     *
     * Compares the response time against the monitor's timeout.
     * If response time is > 80% of timeout, consider it degraded.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param int $responseTime Response time in milliseconds
     * @return bool True if degraded
     */
    protected function isDegraded(Monitor $monitor, int $responseTime): bool
    {
        $timeoutMs = $monitor->timeout * 1000;
        $degradedThreshold = $timeoutMs * 0.8;

        return $responseTime >= $degradedThreshold;
    }

    /**
     * Validate basic monitor configuration
     *
     * Checks common fields that all monitors should have.
     * Subclasses can override to add type-specific validation.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        // Check required fields
        if (empty($monitor->target)) {
            Log::warning("Monitor {$monitor->id} has no target configured");

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
     * Default implementation returns lowercase class name without "Checker" suffix.
     * Subclasses can override for custom identifiers.
     *
     * @return string Checker type
     */
    public function getType(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();

        return strtolower(str_replace('Checker', '', $className));
    }

    /**
     * Get human-readable checker name
     *
     * Default implementation returns capitalized type.
     * Subclasses can override for custom names.
     *
     * @return string Checker name
     */
    public function getName(): string
    {
        return ucfirst($this->getType()) . ' Checker';
    }
}
