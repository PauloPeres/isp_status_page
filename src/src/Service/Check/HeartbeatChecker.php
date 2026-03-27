<?php
declare(strict_types=1);

namespace App\Service\Check;

use App\Model\Entity\Monitor;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

/**
 * Heartbeat Checker
 *
 * Checks if a heartbeat monitor has received a ping within
 * the expected interval plus grace period. This is a push-based
 * monitor — services send pings to report they are alive.
 */
class HeartbeatChecker extends AbstractChecker
{
    /**
     * Execute heartbeat check
     *
     * Looks up the heartbeat record for this monitor and checks
     * if last_ping_at + expected_interval + grace_period > now.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to check
     * @return array Check result
     */
    protected function executeCheck(Monitor $monitor): array
    {
        $startTime = microtime(true);

        $heartbeatsTable = TableRegistry::getTableLocator()->get('Heartbeats');

        $heartbeat = $heartbeatsTable->find()
            ->where(['monitor_id' => $monitor->id])
            ->first();

        if ($heartbeat === null) {
            return $this->buildErrorResult(
                'No heartbeat configuration found for this monitor',
                $this->calculateResponseTime($startTime)
            );
        }

        $responseTime = $this->calculateResponseTime($startTime);

        // If never pinged, it's down
        if ($heartbeat->last_ping_at === null) {
            return $this->buildErrorResult(
                'Heartbeat has never been pinged',
                $responseTime,
                [
                    'token' => $heartbeat->token,
                    'expected_interval' => $heartbeat->expected_interval,
                    'grace_period' => $heartbeat->grace_period,
                ]
            );
        }

        $gracePeriod = $heartbeat->grace_period ?? 60;
        $deadline = $heartbeat->last_ping_at->addSeconds(
            $heartbeat->expected_interval + $gracePeriod
        );

        $now = DateTime::now();

        if ($deadline->greaterThan($now)) {
            // Service is pinging on time
            $lastPingAgo = $now->diffInSeconds($heartbeat->last_ping_at);

            return $this->buildSuccessResult(
                $responseTime,
                null,
                [
                    'token' => $heartbeat->token,
                    'last_ping_at' => $heartbeat->last_ping_at->toIso8601String(),
                    'last_ping_ago_seconds' => $lastPingAgo,
                    'expected_interval' => $heartbeat->expected_interval,
                    'grace_period' => $gracePeriod,
                ]
            );
        }

        // Service stopped pinging
        $overdueSeconds = $now->diffInSeconds($deadline);

        Log::warning("Heartbeat overdue for monitor {$monitor->id} by {$overdueSeconds}s");

        return $this->buildErrorResult(
            "Heartbeat overdue — last ping was at {$heartbeat->last_ping_at->toIso8601String()}",
            $responseTime,
            [
                'token' => $heartbeat->token,
                'last_ping_at' => $heartbeat->last_ping_at->toIso8601String(),
                'overdue_seconds' => $overdueSeconds,
                'expected_interval' => $heartbeat->expected_interval,
                'grace_period' => $gracePeriod,
            ]
        );
    }

    /**
     * Validate monitor configuration
     *
     * Heartbeat monitors only need an ID to look up the heartbeat record.
     * We override parent validation since heartbeat monitors don't need
     * a traditional target or timeout.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor to validate
     * @return bool True if valid
     */
    public function validateConfiguration(Monitor $monitor): bool
    {
        if (empty($monitor->id)) {
            Log::warning('Heartbeat monitor has no ID');

            return false;
        }

        return true;
    }

    /**
     * Get checker type identifier
     *
     * @return string Checker type
     */
    public function getType(): string
    {
        return 'heartbeat';
    }

    /**
     * Get human-readable checker name
     *
     * @return string Checker name
     */
    public function getName(): string
    {
        return 'Heartbeat Checker';
    }
}
