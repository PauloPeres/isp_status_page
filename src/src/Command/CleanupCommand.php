<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Database\Exception\DatabaseException;

/**
 * Cleanup Command
 *
 * Cleans up old data from the database to maintain performance
 * and prevent excessive disk usage.
 */
class CleanupCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->setDescription('Clean up old data from database')
            ->addOption('checks-days', [
                'short' => 'c',
                'help' => 'Delete monitor checks older than N days (default: 30)',
                'default' => 30,
            ])
            ->addOption('logs-days', [
                'short' => 'l',
                'help' => 'Delete integration logs older than N days (default: 7)',
                'default' => 7,
            ])
            ->addOption('alerts-days', [
                'short' => 'a',
                'help' => 'Delete alert logs older than N days (default: 30)',
                'default' => 30,
            ])
            ->addOption('vacuum', [
                'short' => 'v',
                'help' => 'Run VACUUM on SQLite database to reclaim space',
                'boolean' => true,
                'default' => true,
            ])
            ->addOption('dry-run', [
                'short' => 'd',
                'help' => 'Show what would be deleted without actually deleting',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * Execute cleanup command
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('<info>Starting database cleanup...</info>');
        $io->hr();

        $checksDays = (int)$args->getOption('checks-days');
        $logsDays = (int)$args->getOption('logs-days');
        $alertsDays = (int)$args->getOption('alerts-days');
        $vacuum = (bool)$args->getOption('vacuum');
        $dryRun = (bool)$args->getOption('dry-run');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No data will be deleted');
            $io->hr();
        }

        $totalDeleted = 0;

        // Clean up monitor checks
        $deleted = $this->cleanupMonitorChecks($checksDays, $dryRun, $io);
        $totalDeleted += $deleted;

        // Clean up integration logs
        $deleted = $this->cleanupIntegrationLogs($logsDays, $dryRun, $io);
        $totalDeleted += $deleted;

        // Clean up alert logs
        $deleted = $this->cleanupAlertLogs($alertsDays, $dryRun, $io);
        $totalDeleted += $deleted;

        // Vacuum database if requested and not dry run
        if ($vacuum && !$dryRun) {
            $this->vacuumDatabase($io);
        }

        $io->hr();
        $io->out("<success>Cleanup completed!</success>");
        $io->out("Total records deleted: <info>{$totalDeleted}</info>");

        if ($dryRun) {
            $io->out('<warning>This was a dry run. Run without --dry-run to actually delete data.</warning>');
        }

        return static::CODE_SUCCESS;
    }

    /**
     * Clean up old monitor checks
     *
     * @param int $days Delete checks older than this many days
     * @param bool $dryRun Don't actually delete if true
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int Number of records that would be/were deleted
     */
    private function cleanupMonitorChecks(int $days, bool $dryRun, ConsoleIo $io): int
    {
        $io->out("<info>Cleaning monitor checks older than {$days} days...</info>");

        $table = $this->fetchTable('MonitorChecks');
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $query = $table->find()
            ->where(['checked_at <' => $cutoffDate]);

        $count = $query->count();

        if ($count === 0) {
            $io->out('  <success>✓</success> No old monitor checks to delete');
            return 0;
        }

        if ($dryRun) {
            $io->out("  <warning>Would delete {$count} monitor checks</warning>");
            return $count;
        }

        try {
            $deleted = $table->deleteAll(['checked_at <' => $cutoffDate]);
            $io->out("  <success>✓</success> Deleted {$deleted} monitor checks");
            return $deleted;
        } catch (DatabaseException $e) {
            $io->error('  ✗ Error deleting monitor checks: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up old integration logs
     *
     * @param int $days Delete logs older than this many days
     * @param bool $dryRun Don't actually delete if true
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int Number of records that would be/were deleted
     */
    private function cleanupIntegrationLogs(int $days, bool $dryRun, ConsoleIo $io): int
    {
        $io->out("<info>Cleaning integration logs older than {$days} days...</info>");

        $table = $this->fetchTable('IntegrationLogs');
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $query = $table->find()
            ->where(['created <' => $cutoffDate]);

        $count = $query->count();

        if ($count === 0) {
            $io->out('  <success>✓</success> No old integration logs to delete');
            return 0;
        }

        if ($dryRun) {
            $io->out("  <warning>Would delete {$count} integration logs</warning>");
            return $count;
        }

        try {
            $deleted = $table->deleteAll(['created <' => $cutoffDate]);
            $io->out("  <success>✓</success> Deleted {$deleted} integration logs");
            return $deleted;
        } catch (DatabaseException $e) {
            $io->error('  ✗ Error deleting integration logs: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up old alert logs
     *
     * @param int $days Delete logs older than this many days
     * @param bool $dryRun Don't actually delete if true
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int Number of records that would be/were deleted
     */
    private function cleanupAlertLogs(int $days, bool $dryRun, ConsoleIo $io): int
    {
        $io->out("<info>Cleaning alert logs older than {$days} days...</info>");

        $table = $this->fetchTable('AlertLogs');
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $query = $table->find()
            ->where(['created <' => $cutoffDate]);

        $count = $query->count();

        if ($count === 0) {
            $io->out('  <success>✓</success> No old alert logs to delete');
            return 0;
        }

        if ($dryRun) {
            $io->out("  <warning>Would delete {$count} alert logs</warning>");
            return $count;
        }

        try {
            $deleted = $table->deleteAll(['created <' => $cutoffDate]);
            $io->out("  <success>✓</success> Deleted {$deleted} alert logs");
            return $deleted;
        } catch (DatabaseException $e) {
            $io->error('  ✗ Error deleting alert logs: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Run VACUUM on SQLite database to reclaim space
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return void
     */
    private function vacuumDatabase(ConsoleIo $io): void
    {
        $io->out('<info>Running VACUUM to reclaim disk space...</info>');

        try {
            $connection = $this->fetchTable('MonitorChecks')->getConnection();

            // Check if it's SQLite
            $driver = $connection->getDriver();
            if (strpos(get_class($driver), 'Sqlite') === false) {
                $io->out('  <warning>⚠</warning> VACUUM is only supported for SQLite databases');
                return;
            }

            $connection->execute('VACUUM');
            $io->out('  <success>✓</success> Database vacuumed successfully');
        } catch (\Exception $e) {
            $io->error('  ✗ Error running VACUUM: ' . $e->getMessage());
        }
    }
}
