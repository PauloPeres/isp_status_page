<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\ScheduledReportService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Send Scheduled Reports Command (P4-010)
 *
 * Processes all due scheduled reports and sends them via email.
 * Intended to be run hourly via cron.
 */
class SendScheduledReportsCommand extends Command
{
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
            ->setDescription('Process and send all due scheduled email reports.')
            ->addOption('dry-run', [
                'help' => 'List due reports without sending them',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * Execute the command.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $io->out('Processing scheduled reports...');

        $service = new ScheduledReportService();

        if ($args->getOption('dry-run')) {
            $scheduledReportsTable = $this->fetchTable('ScheduledReports');
            $dueReports = $scheduledReportsTable->find('due')->all();

            $count = $dueReports->count();
            $io->out("Found {$count} due report(s):");

            foreach ($dueReports as $report) {
                $io->out("  - #{$report->id} \"{$report->name}\" ({$report->frequency}) -> " .
                    implode(', ', $report->getRecipientsArray()));
            }

            $io->success('Dry run complete. No reports were sent.');
            return self::CODE_SUCCESS;
        }

        try {
            $processed = $service->processDueReports();
            $io->success("Processed {$processed} scheduled report(s).");

            $this->log("SendScheduledReports: processed {$processed} report(s).", 'info');
        } catch (\Exception $e) {
            $io->error('Error processing scheduled reports: ' . $e->getMessage());
            $this->log('SendScheduledReports error: ' . $e->getMessage(), 'error');
            return self::CODE_ERROR;
        }

        return self::CODE_SUCCESS;
    }
}
