<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;

/**
 * ManagePartitions Command
 *
 * TASK-DB-006: Manages weekly partitions for the monitor_checks table.
 * - Creates partitions for the next 4 weeks (if they don't already exist)
 * - Drops partitions older than the maximum retention period (90 days + buffer)
 *
 * Should be run weekly via cron:
 *   bin/cake manage_partitions
 */
class ManagePartitionsCommand extends Command
{
    /**
     * Default retention buffer in days.
     * Partitions older than this are dropped.
     * Set to 100 days (90-day max retention for business plan + 10-day buffer).
     *
     * @var int
     */
    protected const RETENTION_DAYS = 100;

    /**
     * Number of future weeks to pre-create partitions for.
     *
     * @var int
     */
    protected const FUTURE_WEEKS = 4;

    /**
     * Build the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to configure.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Manage weekly partitions for monitor_checks table.')
            ->addOption('retention-days', [
                'help' => 'Number of days to retain partitions (default: 100)',
                'default' => (string)static::RETENTION_DAYS,
                'short' => 'r',
            ])
            ->addOption('dry-run', [
                'help' => 'Show what would be done without making changes',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * Execute the command.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $connection = ConnectionManager::get('default');
        $dryRun = (bool)$args->getOption('dry-run');
        $retentionDays = (int)$args->getOption('retention-days');

        if ($dryRun) {
            $io->info('Running in dry-run mode. No changes will be made.');
        }

        $io->info('Managing partitions for monitor_checks...');

        // Create future partitions
        $created = $this->createFuturePartitions($connection, $io, $dryRun);

        // Drop expired partitions
        $dropped = $this->dropExpiredPartitions($connection, $io, $dryRun, $retentionDays);

        $io->info(sprintf(
            'Done. Created: %d partition(s), Dropped: %d partition(s).',
            $created,
            $dropped
        ));

        return static::CODE_SUCCESS;
    }

    /**
     * Create partitions for the current week and the next FUTURE_WEEKS weeks.
     *
     * @param \Cake\Database\Connection $connection Database connection.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @param bool $dryRun Whether to skip actual execution.
     * @return int Number of partitions created.
     */
    protected function createFuturePartitions($connection, ConsoleIo $io, bool $dryRun): int
    {
        $created = 0;
        $startOfWeek = new \DateTime('monday this week');

        for ($i = 0; $i <= static::FUTURE_WEEKS; $i++) {
            $start = clone $startOfWeek;
            $start->modify("+{$i} weeks");
            $end = clone $start;
            $end->modify('+1 week');
            $name = 'monitor_checks_' . $start->format('Y') . 'w' . $start->format('W');

            // Check if partition already exists
            $exists = $connection->execute(
                "SELECT 1 FROM pg_class WHERE relname = :name",
                ['name' => $name]
            )->fetch();

            if (!$exists) {
                $sql = sprintf(
                    "CREATE TABLE %s PARTITION OF monitor_checks FOR VALUES FROM ('%s') TO ('%s')",
                    $name,
                    $start->format('Y-m-d'),
                    $end->format('Y-m-d')
                );

                if ($dryRun) {
                    $io->info("[DRY-RUN] Would create partition: {$name}");
                } else {
                    $connection->execute($sql);
                    $io->success("Created partition: {$name}");
                }
                $created++;
            }
        }

        return $created;
    }

    /**
     * Drop partitions older than the retention period.
     *
     * @param \Cake\Database\Connection $connection Database connection.
     * @param \Cake\Console\ConsoleIo $io Console IO.
     * @param bool $dryRun Whether to skip actual execution.
     * @param int $retentionDays Number of days to retain.
     * @return int Number of partitions dropped.
     */
    protected function dropExpiredPartitions($connection, ConsoleIo $io, bool $dryRun, int $retentionDays): int
    {
        $dropped = 0;
        $dropBefore = new \DateTime("-{$retentionDays} days");
        $dropBeforeWeek = 'monitor_checks_' . $dropBefore->format('Y') . 'w' . $dropBefore->format('W');

        // List all weekly partitions (matching pattern monitor_checks_YYYYwWW)
        $partitions = $connection->execute(
            "SELECT relname FROM pg_class WHERE relname LIKE 'monitor_checks_%w%' ORDER BY relname"
        )->fetchAll('assoc');

        foreach ($partitions as $partition) {
            $name = $partition['relname'];

            // Skip the _old backup table if it still exists
            if ($name === 'monitor_checks_old') {
                continue;
            }

            // Only drop partitions whose name sorts before the cutoff
            if ($name < $dropBeforeWeek) {
                if ($dryRun) {
                    $io->info("[DRY-RUN] Would drop expired partition: {$name}");
                } else {
                    $connection->execute("DROP TABLE IF EXISTS {$name}");
                    $io->success("Dropped expired partition: {$name}");
                }
                $dropped++;
            }
        }

        return $dropped;
    }
}
