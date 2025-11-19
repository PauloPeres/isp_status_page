<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Filesystem\Folder;

/**
 * Backup Command
 *
 * Creates backups of the database and rotates old backups
 * to prevent excessive disk usage.
 */
class BackupCommand extends Command
{
    /**
     * Default backup directory
     */
    private const DEFAULT_BACKUP_DIR = ROOT . DS . 'backups';

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
            ->setDescription('Create database backup with automatic rotation')
            ->addOption('dir', [
                'short' => 'd',
                'help' => 'Backup directory (default: ROOT/backups)',
                'default' => self::DEFAULT_BACKUP_DIR,
            ])
            ->addOption('keep', [
                'short' => 'k',
                'help' => 'Number of backups to keep (default: 30)',
                'default' => 30,
            ])
            ->addOption('compress', [
                'short' => 'c',
                'help' => 'Compress backup with gzip',
                'boolean' => true,
                'default' => false,
            ])
            ->addOption('prefix', [
                'short' => 'p',
                'help' => 'Backup file prefix (default: backup)',
                'default' => 'backup',
            ]);

        return $parser;
    }

    /**
     * Execute backup command
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('<info>Starting database backup...</info>');
        $io->hr();

        $backupDir = (string)$args->getOption('dir');
        $keepCount = (int)$args->getOption('keep');
        $compress = (bool)$args->getOption('compress');
        $prefix = (string)$args->getOption('prefix');

        // Create backup directory if it doesn't exist
        if (!$this->ensureBackupDirectory($backupDir, $io)) {
            return static::CODE_ERROR;
        }

        // Get database file path
        $dbPath = $this->getDatabasePath();
        if (!$dbPath) {
            $io->error('Could not determine database file path');
            $io->out('Make sure you are using SQLite database');
            return static::CODE_ERROR;
        }

        if (!file_exists($dbPath)) {
            $io->error("Database file not found: {$dbPath}");
            return static::CODE_ERROR;
        }

        // Create backup
        $backupFile = $this->createBackup($dbPath, $backupDir, $prefix, $compress, $io);
        if (!$backupFile) {
            return static::CODE_ERROR;
        }

        // Rotate old backups
        $this->rotateBackups($backupDir, $prefix, $keepCount, $io);

        $io->hr();
        $io->out('<success>Backup completed successfully!</success>');
        $io->out("Backup file: <info>{$backupFile}</info>");

        $filesize = $this->formatBytes(filesize($backupFile));
        $io->out("File size: <info>{$filesize}</info>");

        return static::CODE_SUCCESS;
    }

    /**
     * Ensure backup directory exists
     *
     * @param string $dir Backup directory path
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return bool True if directory exists or was created
     */
    private function ensureBackupDirectory(string $dir, ConsoleIo $io): bool
    {
        if (is_dir($dir)) {
            $io->out("Backup directory: <info>{$dir}</info>");
            return true;
        }

        $io->out("Creating backup directory: <info>{$dir}</info>");

        $folder = new Folder();
        if ($folder->create($dir, 0755)) {
            $io->out('  <success>✓</success> Directory created');
            return true;
        }

        $io->error("  ✗ Failed to create directory: {$dir}");
        return false;
    }

    /**
     * Get database file path
     *
     * @return string|null Database file path or null if not found
     */
    private function getDatabasePath(): ?string
    {
        // Try to get from configuration
        $datasource = Configure::read('Datasources.default');

        if (!$datasource) {
            return null;
        }

        // Check if it's SQLite
        if (isset($datasource['driver']) && strpos($datasource['driver'], 'Sqlite') !== false) {
            // Get database path
            if (isset($datasource['database'])) {
                $dbPath = $datasource['database'];

                // Handle relative paths
                if ($dbPath[0] !== '/') {
                    $dbPath = ROOT . DS . $dbPath;
                }

                return $dbPath;
            }
        }

        return null;
    }

    /**
     * Create database backup
     *
     * @param string $dbPath Database file path
     * @param string $backupDir Backup directory
     * @param string $prefix Backup file prefix
     * @param bool $compress Whether to compress the backup
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return string|null Backup file path or null on failure
     */
    private function createBackup(
        string $dbPath,
        string $backupDir,
        string $prefix,
        bool $compress,
        ConsoleIo $io
    ): ?string {
        // Generate backup filename with timestamp
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$prefix}_{$timestamp}.db";

        if ($compress) {
            $filename .= '.gz';
        }

        $backupPath = $backupDir . DS . $filename;

        $io->out("Creating backup: <info>{$filename}</info>");

        try {
            if ($compress) {
                // Compress while copying
                $sourceHandle = fopen($dbPath, 'rb');
                $destHandle = gzopen($backupPath, 'wb9');

                if (!$sourceHandle || !$destHandle) {
                    throw new \RuntimeException('Failed to open files for compression');
                }

                while (!feof($sourceHandle)) {
                    gzwrite($destHandle, fread($sourceHandle, 1024 * 512));
                }

                fclose($sourceHandle);
                gzclose($destHandle);
            } else {
                // Simple copy
                if (!copy($dbPath, $backupPath)) {
                    throw new \RuntimeException('Failed to copy database file');
                }
            }

            $io->out('  <success>✓</success> Backup created successfully');
            return $backupPath;
        } catch (\Exception $e) {
            $io->error('  ✗ Error creating backup: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Rotate old backups
     *
     * @param string $backupDir Backup directory
     * @param string $prefix Backup file prefix
     * @param int $keepCount Number of backups to keep
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return void
     */
    private function rotateBackups(string $backupDir, string $prefix, int $keepCount, ConsoleIo $io): void
    {
        $io->out("Rotating backups (keeping last {$keepCount})...");

        // Get all backup files
        $folder = new Folder($backupDir);
        $files = $folder->find("{$prefix}_.*\.db(\.gz)?");

        if (count($files) <= $keepCount) {
            $io->out('  <success>✓</success> No old backups to remove');
            return;
        }

        // Sort by modification time (oldest first)
        usort($files, function ($a, $b) use ($backupDir) {
            $timeA = filemtime($backupDir . DS . $a);
            $timeB = filemtime($backupDir . DS . $b);
            return $timeA - $timeB;
        });

        // Delete oldest backups
        $deleteCount = count($files) - $keepCount;
        $deleted = 0;

        for ($i = 0; $i < $deleteCount; $i++) {
            $file = $backupDir . DS . $files[$i];
            if (unlink($file)) {
                $deleted++;
                $io->verbose("  Deleted: {$files[$i]}");
            }
        }

        if ($deleted > 0) {
            $io->out("  <success>✓</success> Removed {$deleted} old backup(s)");
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes Bytes
     * @return string Formatted string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}
