<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Check\CheckService;
use App\Service\Check\HttpChecker;
use App\Service\Check\PingChecker;
use App\Service\Check\PortChecker;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;

/**
 * Monitor Check Command
 *
 * Executes health checks for all active monitors.
 * This command should be run periodically via cron (every 30 seconds recommended).
 *
 * Usage:
 * - Check all monitors: bin/cake monitor_check
 * - Check specific monitor: bin/cake monitor_check --monitor-id 5
 * - Verbose output: bin/cake monitor_check -v
 */
class MonitorCheckCommand extends Command
{
    /**
     * Check service instance
     *
     * @var \App\Service\Check\CheckService
     */
    protected CheckService $checkService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Initialize check service and register all checkers
        $this->checkService = new CheckService();
        $this->checkService->registerChecker(new HttpChecker());
        $this->checkService->registerChecker(new PingChecker());
        $this->checkService->registerChecker(new PortChecker());
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription(
                'Execute health checks for all active monitors. ' .
                'This command should be run periodically via cron.'
            )
            ->addOption('monitor-id', [
                'short' => 'm',
                'help' => 'Check only a specific monitor by ID',
                'required' => false,
            ])
            ->addOption('verbose', [
                'short' => 'v',
                'help' => 'Enable verbose output',
                'boolean' => true,
            ]);

        return $parser;
    }

    /**
     * Execute command
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $startTime = microtime(true);
        $monitorId = $args->getOption('monitor-id');

        $io->out('<info>Monitor Check Command Started</info>');
        $io->out('Time: ' . date('Y-m-d H:i:s'));
        $io->hr();

        try {
            // Fetch monitors to check
            $monitors = $this->fetchMonitorsToCheck($monitorId);

            if (empty($monitors)) {
                if ($monitorId !== null) {
                    $io->warning("Monitor ID {$monitorId} not found or not active");
                    Log::warning("Monitor check: Monitor {$monitorId} not found or not active");

                    return self::CODE_ERROR;
                }

                $io->warning('No active monitors found to check');
                Log::info('Monitor check: No active monitors found');

                return self::CODE_SUCCESS;
            }

            $io->out(sprintf('Found %d monitor(s) to check', count($monitors)));
            $io->hr();

            // Execute checks
            $results = $this->executeChecks($monitors, $io, $args->getOption('verbose'));

            // Display summary
            $this->displaySummary($io, $results, microtime(true) - $startTime);

            Log::info('Monitor check completed', [
                'total_monitors' => count($monitors),
                'up' => $results['up'],
                'degraded' => $results['degraded'],
                'down' => $results['down'],
                'errors' => $results['errors'],
                'duration' => round(microtime(true) - $startTime, 2),
            ]);

            return self::CODE_SUCCESS;
        } catch (\Exception $e) {
            $io->error('Monitor check failed: ' . $e->getMessage());
            Log::error('Monitor check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::CODE_ERROR;
        }
    }

    /**
     * Fetch monitors to check
     *
     * @param int|string|null $monitorId Optional specific monitor ID
     * @return array List of monitors
     */
    protected function fetchMonitorsToCheck($monitorId = null): array
    {
        $monitorsTable = $this->fetchTable('Monitors');

        $query = $monitorsTable->find()
            ->where(['active' => true]);

        // Filter by specific monitor if provided
        if ($monitorId !== null) {
            $query->where(['id' => $monitorId]);
        }

        return $query->all()->toArray();
    }

    /**
     * Execute checks for all monitors
     *
     * @param array $monitors List of monitors
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param bool $verbose Verbose output
     * @return array Results summary
     */
    protected function executeChecks(array $monitors, ConsoleIo $io, bool $verbose = false): array
    {
        $results = [
            'up' => 0,
            'degraded' => 0,
            'down' => 0,
            'errors' => 0,
            'total' => count($monitors),
        ];

        foreach ($monitors as $monitor) {
            try {
                // Execute check
                $checkResult = $this->checkService->executeCheck($monitor);

                // Save check result
                $this->saveCheckResult($monitor, $checkResult);

                // Update monitor status
                $this->updateMonitorStatus($monitor, $checkResult);

                // Count results
                $status = $checkResult['status'];
                if (isset($results[$status])) {
                    $results[$status]++;
                }

                // Display progress
                if ($verbose) {
                    $this->displayCheckResult($io, $monitor, $checkResult);
                } else {
                    $icon = $this->getStatusIcon($status);
                    $io->out("{$icon} [{$monitor->id}] {$monitor->name}", 0);
                }
            } catch (\Exception $e) {
                $results['errors']++;

                $io->error("[{$monitor->id}] {$monitor->name}: {$e->getMessage()}");

                Log::error("Check failed for monitor {$monitor->id}", [
                    'monitor_id' => $monitor->id,
                    'monitor_name' => $monitor->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Save check result to database
     *
     * @param \App\Model\Entity\Monitor $monitor Monitor entity
     * @param array $checkResult Check result
     * @return void
     */
    protected function saveCheckResult($monitor, array $checkResult): void
    {
        $monitorChecksTable = $this->fetchTable('MonitorChecks');

        // Map checker status to check status
        $status = $this->mapStatusToCheckStatus($checkResult['status']);

        $checkEntity = $monitorChecksTable->newEntity([
            'monitor_id' => $monitor->id,
            'status' => $status,
            'response_time' => $checkResult['response_time'] ?? null,
            'status_code' => $checkResult['status_code'] ?? null,
            'error_message' => $checkResult['error_message'] ?? null,
            'details' => json_encode($checkResult['metadata'] ?? []),
            'checked_at' => $checkResult['checked_at'] ?? date('Y-m-d H:i:s'),
        ]);

        if (!$monitorChecksTable->save($checkEntity)) {
            Log::error("Failed to save check result for monitor {$monitor->id}", [
                'monitor_id' => $monitor->id,
                'errors' => $checkEntity->getErrors(),
            ]);
        }
    }

    /**
     * Update monitor status and statistics
     *
     * @param \App\Model\Entity\Monitor $monitor Monitor entity
     * @param array $checkResult Check result
     * @return void
     */
    protected function updateMonitorStatus($monitor, array $checkResult): void
    {
        $monitorsTable = $this->fetchTable('Monitors');

        // Update monitor fields
        $monitor->status = $checkResult['status'];
        $monitor->last_check_at = new DateTime($checkResult['checked_at']);

        // Calculate uptime if we have enough data
        $uptime = $this->calculateUptime($monitor->id);
        if ($uptime !== null) {
            $monitor->uptime_percentage = $uptime;
        }

        if (!$monitorsTable->save($monitor)) {
            Log::error("Failed to update monitor {$monitor->id}", [
                'monitor_id' => $monitor->id,
                'errors' => $monitor->getErrors(),
            ]);
        }
    }

    /**
     * Calculate uptime percentage for a monitor
     *
     * @param int $monitorId Monitor ID
     * @return float|null Uptime percentage or null if insufficient data
     */
    protected function calculateUptime(int $monitorId): ?float
    {
        $monitorChecksTable = $this->fetchTable('MonitorChecks');

        // Get checks from last 24 hours
        $cutoffTime = (new DateTime())->modify('-24 hours');

        $query = $monitorChecksTable->find()
            ->where([
                'monitor_id' => $monitorId,
                'checked_at >=' => $cutoffTime,
            ]);

        $totalChecks = $query->count();

        if ($totalChecks === 0) {
            return null; // Not enough data
        }

        // Count successful checks (success and degraded count as up)
        $successfulChecks = $monitorChecksTable->find()
            ->where([
                'monitor_id' => $monitorId,
                'checked_at >=' => $cutoffTime,
                'status IN' => ['success'],
            ])
            ->count();

        return round(($successfulChecks / $totalChecks) * 100, 2);
    }

    /**
     * Map checker status to check status
     *
     * @param string $checkerStatus Checker status (up, down, degraded)
     * @return string Check status (success, failure, timeout, error)
     */
    protected function mapStatusToCheckStatus(string $checkerStatus): string
    {
        return match ($checkerStatus) {
            'up' => 'success',
            'degraded' => 'success', // Degraded still counts as up
            'down' => 'failure',
            default => 'error',
        };
    }

    /**
     * Display check result (verbose mode)
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param \App\Model\Entity\Monitor $monitor Monitor entity
     * @param array $checkResult Check result
     * @return void
     */
    protected function displayCheckResult(ConsoleIo $io, $monitor, array $checkResult): void
    {
        $status = $checkResult['status'];
        $icon = $this->getStatusIcon($status);
        $responseTime = $checkResult['response_time'] ?? 0;

        $io->out(sprintf(
            '%s [%d] %s (%s) - %dms',
            $icon,
            $monitor->id,
            $monitor->name,
            $monitor->type,
            $responseTime
        ));

        if (!empty($checkResult['error_message'])) {
            $io->out("    Error: {$checkResult['error_message']}", 0);
        }
    }

    /**
     * Display summary
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param array $results Results summary
     * @param float $duration Execution duration
     * @return void
     */
    protected function displaySummary(ConsoleIo $io, array $results, float $duration): void
    {
        $io->hr();
        $io->out('<info>Summary:</info>');
        $io->out(sprintf('  Total:    %d monitors', $results['total']));
        $io->out(sprintf('  <success>✓ Up:       %d</success>', $results['up']));
        $io->out(sprintf('  <warning>⚠ Degraded: %d</warning>', $results['degraded']));
        $io->out(sprintf('  <error>✗ Down:     %d</error>', $results['down']));

        if ($results['errors'] > 0) {
            $io->out(sprintf('  <error>⚠ Errors:   %d</error>', $results['errors']));
        }

        $io->out(sprintf('  Duration: %.2f seconds', $duration));
        $io->hr();
    }

    /**
     * Get status icon
     *
     * @param string $status Status
     * @return string Icon
     */
    protected function getStatusIcon(string $status): string
    {
        return match ($status) {
            'up' => '<success>✓</success>',
            'degraded' => '<warning>⚠</warning>',
            'down' => '<error>✗</error>',
            default => '?',
        };
    }
}
