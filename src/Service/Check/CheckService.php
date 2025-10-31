<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\Log\Log;
use RuntimeException;

/**
 * Check Service
 *
 * Main service that coordinates all checker types and executes
 * monitor checks. Acts as a factory and registry for checkers.
 */
class CheckService
{
    /**
     * Registry of available checkers
     *
     * @var array<string, \App\Service\Check\CheckerInterface>
     */
    protected array $checkers = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Checkers will be registered as they are implemented
        // For now, this is just the infrastructure
    }

    /**
     * Register a checker
     *
     * @param \App\Service\Check\CheckerInterface $checker The checker to register
     * @return void
     */
    public function registerChecker(CheckerInterface $checker): void
    {
        $type = $checker->getType();
        $this->checkers[$type] = $checker;

        Log::debug("Registered checker: {$checker->getName()} (type: {$type})");
    }

    /**
     * Get a checker by type
     *
     * @param string $type Checker type (e.g., 'http', 'ping', 'port')
     * @return \App\Service\Check\CheckerInterface|null The checker or null if not found
     */
    public function getChecker(string $type): ?CheckerInterface
    {
        return $this->checkers[$type] ?? null;
    }

    /**
     * Check if a checker is registered for the given type
     *
     * @param string $type Checker type
     * @return bool True if checker is registered
     */
    public function hasChecker(string $type): bool
    {
        return isset($this->checkers[$type]);
    }

    /**
     * Get all registered checkers
     *
     * @return array<string, \App\Service\Check\CheckerInterface> Array of checkers keyed by type
     */
    public function getCheckers(): array
    {
        return $this->checkers;
    }

    /**
     * Execute a check for the given monitor
     *
     * This is the main method that determines which checker to use
     * and executes the check.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     * @throws \RuntimeException If no checker is available for the monitor type
     */
    public function executeCheck(Monitor $monitor): array
    {
        // Validate monitor type
        if (empty($monitor->type)) {
            throw new RuntimeException("Monitor {$monitor->id} has no type defined");
        }

        // Get appropriate checker
        $checker = $this->getChecker($monitor->type);

        if ($checker === null) {
            throw new RuntimeException(
                "No checker registered for type: {$monitor->type}"
            );
        }

        // Execute the check
        Log::info("Executing check for monitor: {$monitor->name}", [
            'monitor_id' => $monitor->id,
            'type' => $monitor->type,
            'target' => $monitor->target,
        ]);

        $result = $checker->check($monitor);

        // Add timestamp to result
        $result['checked_at'] = date('Y-m-d H:i:s');

        Log::info("Check completed for monitor: {$monitor->name}", [
            'monitor_id' => $monitor->id,
            'status' => $result['status'],
            'response_time' => $result['response_time'] ?? null,
        ]);

        return $result;
    }

    /**
     * Execute checks for multiple monitors
     *
     * @param array<\App\Model\Entity\Monitor> $monitors Array of monitors to check
     * @return array<int, array> Array of check results keyed by monitor ID
     */
    public function executeChecks(array $monitors): array
    {
        $results = [];

        foreach ($monitors as $monitor) {
            try {
                $results[$monitor->id] = $this->executeCheck($monitor);
            } catch (\Exception $e) {
                Log::error("Failed to execute check for monitor {$monitor->id}", [
                    'error' => $e->getMessage(),
                ]);

                // Store error result
                $results[$monitor->id] = [
                    'status' => 'down',
                    'response_time' => 0,
                    'status_code' => null,
                    'error_message' => $e->getMessage(),
                    'metadata' => [],
                    'checked_at' => date('Y-m-d H:i:s'),
                ];
            }
        }

        return $results;
    }

    /**
     * Validate monitor configuration for its type
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if configuration is valid
     */
    public function validateMonitorConfiguration(Monitor $monitor): bool
    {
        $checker = $this->getChecker($monitor->type);

        if ($checker === null) {
            Log::warning("Cannot validate monitor {$monitor->id}: no checker for type {$monitor->type}");

            return false;
        }

        return $checker->validateConfiguration($monitor);
    }

    /**
     * Get statistics about registered checkers
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_checkers' => count($this->checkers),
            'checker_types' => array_keys($this->checkers),
            'checkers' => [],
        ];

        foreach ($this->checkers as $type => $checker) {
            $stats['checkers'][$type] = [
                'type' => $type,
                'name' => $checker->getName(),
                'class' => get_class($checker),
            ];
        }

        return $stats;
    }
}
