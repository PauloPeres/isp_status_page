<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;

/**
 * Checker Interface
 *
 * Defines the contract that all monitor checkers must implement.
 * Each checker is responsible for verifying a specific type of monitor
 * (HTTP, Ping, Port, etc).
 */
interface CheckerInterface
{
    /**
     * Execute the check for the given monitor
     *
     * This method performs the actual verification of the monitor's target
     * and returns a standardized result array.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result with the following keys:
     *   - status: string ('up', 'down', 'degraded')
     *   - response_time: int|null Response time in milliseconds
     *   - status_code: int|null HTTP status code or port number (if applicable)
     *   - error_message: string|null Error message if check failed
     *   - metadata: array Additional checker-specific data
     */
    public function check(Monitor $monitor): array;

    /**
     * Validate monitor configuration for this checker type
     *
     * Checks if the monitor has all required fields properly configured
     * for this checker type.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid, false otherwise
     */
    public function validateConfiguration(Monitor $monitor): bool;

    /**
     * Get the checker type identifier
     *
     * Returns a unique string identifier for this checker type
     * (e.g., 'http', 'ping', 'port').
     *
     * @return string Checker type identifier
     */
    public function getType(): string;

    /**
     * Get human-readable name for this checker
     *
     * @return string Checker display name
     */
    public function getName(): string;
}
