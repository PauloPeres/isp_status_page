<?php
declare(strict_types=1);

namespace App\Command;

use App\Job\EscalationJob;
use App\Job\MonitorCheckJob;
use App\Service\RedisLockService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\QueueManager;

/**
 * Scheduler Command
 *
 * Runs as a daemon (or single-cycle with --once) to schedule monitor checks
 * and escalation processing via the queue system. Uses a Redis lock to ensure
 * only one scheduler instance runs across multiple nodes.
 *
 * Usage:
 * - Daemon mode: bin/cake scheduler
 * - Single cycle (cron): bin/cake scheduler --once
 */
class SchedulerCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * Cycle interval in seconds.
     *
     * @var int
     */
    protected const CYCLE_INTERVAL = 30;

    /**
     * Redis lock TTL in seconds (slightly less than 2x the cycle interval
     * to allow for processing time while preventing stale locks).
     *
     * @var int
     */
    protected const LOCK_TTL = 50;

    /**
     * Lock key for the scheduler.
     *
     * @var string
     */
    protected const LOCK_KEY = 'scheduler';

    /**
     * Build the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription(
                'Schedule monitor checks and escalation processing via the queue. ' .
                'Runs as a daemon by default, or use --once for cron compatibility.'
            )
            ->addOption('once', [
                'help' => 'Run a single scheduling cycle then exit (for cron usage)',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * Execute the scheduler command.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $once = (bool)$args->getOption('once');

        $io->out('<info>Scheduler started</info> (' . ($once ? 'single cycle' : 'daemon mode') . ')');
        $io->out('Cycle interval: ' . self::CYCLE_INTERVAL . 's');
        Log::info('Scheduler started (' . ($once ? 'once' : 'daemon') . ' mode)');

        if ($once) {
            return $this->runCycle($io);
        }

        // Daemon loop
        while (true) {
            $cycleStart = microtime(true);

            $this->runCycle($io);

            // Sleep until the next cycle boundary
            $elapsed = microtime(true) - $cycleStart;
            $sleepTime = max(0, self::CYCLE_INTERVAL - $elapsed);

            if ($sleepTime > 0) {
                $io->verbose(sprintf('Sleeping %.1fs until next cycle', $sleepTime));
                usleep((int)($sleepTime * 1_000_000));
            }
        }

        // @phpstan-ignore-next-line (unreachable, but satisfies return type)
        return static::CODE_SUCCESS;
    }

    /**
     * Run a single scheduling cycle.
     *
     * Acquires a Redis lock, schedules due monitors and escalations,
     * then releases the lock.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int Exit code
     */
    protected function runCycle(ConsoleIo $io): int
    {
        $cycleStart = microtime(true);

        // Acquire Redis lock
        $lockService = $this->createLockService($io);
        if ($lockService === null) {
            $io->warning('Could not create Redis lock service, skipping cycle');

            return static::CODE_SUCCESS;
        }

        if (!$lockService->acquire(self::LOCK_KEY, self::LOCK_TTL)) {
            $io->verbose('Lock not acquired (another scheduler node is active), skipping cycle');

            return static::CODE_SUCCESS;
        }

        try {
            $io->verbose('Lock acquired, running scheduling cycle at ' . date('Y-m-d H:i:s'));

            // Step 1: Schedule due monitor checks
            $monitorsScheduled = $this->scheduleMonitorChecks($io);

            // Step 2: Schedule escalation processing
            $escalationsScheduled = $this->scheduleEscalations($io);

            $elapsed = round((microtime(true) - $cycleStart) * 1000);
            $io->verbose("Cycle complete: {$monitorsScheduled} monitors, {$escalationsScheduled} escalations ({$elapsed}ms)");

            if ($monitorsScheduled > 0 || $escalationsScheduled > 0) {
                Log::info("Scheduler cycle: {$monitorsScheduled} monitors queued, {$escalationsScheduled} escalations queued ({$elapsed}ms)");
            }

            // Record heartbeat so the queue dashboard knows the scheduler is alive
            $this->recordHeartbeat($io);

            return static::CODE_SUCCESS;
        } catch (\Exception $e) {
            $io->error("Scheduler cycle failed: {$e->getMessage()}");
            Log::error("Scheduler cycle failed: {$e->getMessage()}");

            return static::CODE_ERROR;
        } finally {
            $lockService->release(self::LOCK_KEY);
        }
    }

    /**
     * Schedule monitor checks for all due monitors.
     *
     * Queries active monitors where next_check_at is NULL or in the past,
     * pushes a MonitorCheckJob for each, and updates next_check_at.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int Number of monitors scheduled
     */
    protected function scheduleMonitorChecks(ConsoleIo $io): int
    {
        $monitorsTable = $this->fetchTable('Monitors');
        $now = new DateTime();
        $count = 0;

        try {
            $dueMonitors = $monitorsTable->find()
                ->where([
                    'active' => true,
                    'OR' => [
                        'next_check_at IS' => null,
                        'next_check_at <=' => $now,
                    ],
                ])
                ->all();

            foreach ($dueMonitors as $monitor) {
                try {
                    QueueManager::push(MonitorCheckJob::class, [
                        'data' => [
                            'monitor_id' => $monitor->id,
                            'region_id' => null,
                        ],
                    ], ['config' => 'default']);

                    // Update next_check_at = now + check_interval seconds
                    $interval = (int)($monitor->check_interval ?: 60);
                    $monitor->next_check_at = (new DateTime())->modify("+{$interval} seconds");
                    $monitorsTable->save($monitor);

                    $count++;
                    $io->verbose("  Queued check for monitor #{$monitor->id} ({$monitor->name}), next at {$monitor->next_check_at}");
                } catch (\Exception $e) {
                    Log::error("Scheduler: Failed to queue check for monitor #{$monitor->id}: {$e->getMessage()}");
                    $io->warning("  Failed to queue monitor #{$monitor->id}: {$e->getMessage()}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Scheduler: Failed to query due monitors: {$e->getMessage()}");
            $io->error("Failed to query due monitors: {$e->getMessage()}");
        }

        return $count;
    }

    /**
     * Schedule escalation processing for unresolved, unacknowledged incidents.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int Number of escalations scheduled
     */
    protected function scheduleEscalations(ConsoleIo $io): int
    {
        $incidentsTable = $this->fetchTable('Incidents');
        $count = 0;

        try {
            $incidents = $incidentsTable->find()
                ->where([
                    'Incidents.status !=' => 'resolved',
                    'Incidents.acknowledged_at IS' => null,
                ])
                ->contain(['Monitors'])
                ->all();

            foreach ($incidents as $incident) {
                // Skip incidents without a monitor or without an escalation policy
                if (!$incident->monitor || !$incident->monitor->escalation_policy_id) {
                    continue;
                }

                try {
                    QueueManager::push(EscalationJob::class, [
                        'data' => [
                            'incident_id' => $incident->id,
                        ],
                    ], ['config' => 'default']);

                    $count++;
                    $io->verbose("  Queued escalation for incident #{$incident->id}");
                } catch (\Exception $e) {
                    Log::error("Scheduler: Failed to queue escalation for incident #{$incident->id}: {$e->getMessage()}");
                    $io->warning("  Failed to queue escalation for incident #{$incident->id}: {$e->getMessage()}");
                }
            }
        } catch (\Exception $e) {
            Log::error("Scheduler: Failed to query incidents for escalation: {$e->getMessage()}");
            $io->error("Failed to query incidents for escalation: {$e->getMessage()}");
        }

        return $count;
    }

    /**
     * Record a scheduler heartbeat in Redis.
     *
     * Sets the key `keepup:scheduler:last_tick` with a 90-second TTL.
     * If the scheduler dies the key expires and the dashboard reports "stopped".
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    protected function recordHeartbeat(ConsoleIo $io): void
    {
        try {
            $redis = new \Redis();
            $redisUrl = getenv('REDIS_URL') ?: '';
            $host = '127.0.0.1';
            $port = 6379;
            $password = '';

            if ($redisUrl) {
                $parsed = parse_url($redisUrl);
                $host = $parsed['host'] ?? '127.0.0.1';
                $port = $parsed['port'] ?? 6379;
                $password = $parsed['pass'] ?? '';
            }

            $redis->connect($host, $port, 2.0);
            if ($password !== '') {
                $redis->auth($password);
            }
            $redis->select(6);

            $now = (new DateTime())->toIso8601String();
            $redis->setex('keepup:scheduler:last_tick', 90, $now);
            $redis->close();

            $io->verbose("Heartbeat recorded at {$now}");
        } catch (\Exception $e) {
            // Non-fatal — the scheduler keeps running even if heartbeat fails
            Log::warning("Scheduler: Failed to record heartbeat: {$e->getMessage()}");
        }
    }

    /**
     * Create a RedisLockService instance, handling connection failures gracefully.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return \App\Service\RedisLockService|null Lock service or null on failure
     */
    protected function createLockService(ConsoleIo $io): ?RedisLockService
    {
        try {
            return new RedisLockService();
        } catch (\RuntimeException $e) {
            Log::error("Scheduler: Cannot create Redis lock service: {$e->getMessage()}");
            $io->error("Redis lock service unavailable: {$e->getMessage()}");

            return null;
        }
    }
}
