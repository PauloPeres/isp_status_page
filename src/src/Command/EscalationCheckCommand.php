<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\EscalationService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Escalation Check Command
 *
 * Runs every minute via cron to process escalation policies for
 * unresolved, unacknowledged incidents. For each such incident,
 * determines the appropriate escalation step based on time elapsed
 * and sends alerts if the step has not yet been executed.
 *
 * Usage:
 * - bin/cake escalation_check
 * - bin/cake escalation_check -v (verbose)
 */
class EscalationCheckCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * Escalation service instance
     *
     * @var \App\Service\EscalationService
     */
    protected EscalationService $escalationService;

    /**
     * Build the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Process escalation policies for unacknowledged incidents.')
            ->addOption('dry-run', [
                'help' => 'Show what would be done without executing',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * Execute the command.
     *
     * @param \Cake\Console\Arguments $args The command arguments
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $startTime = microtime(true);
        $dryRun = $args->getOption('dry-run');

        $io->verbose('Starting escalation check...');
        Log::info('Escalation check started');

        try {
            $this->escalationService = new EscalationService();
            $incidentsTable = $this->fetchTable('Incidents');

            // Find all unresolved, unacknowledged incidents
            $incidents = $incidentsTable->find()
                ->where([
                    'Incidents.status !=' => 'resolved',
                    'Incidents.acknowledged_at IS' => null,
                ])
                ->contain(['Monitors'])
                ->all();

            $total = $incidents->count();
            $processed = 0;
            $escalated = 0;

            $io->verbose("Found {$total} unacknowledged incident(s) to check");

            foreach ($incidents as $incident) {
                $processed++;

                // Skip incidents without a monitor (shouldn't happen, but be safe)
                if (!$incident->monitor) {
                    $io->verbose("Incident #{$incident->id}: skipped (no monitor)");
                    continue;
                }

                // Skip if monitor has no escalation policy
                if (!$incident->monitor->escalation_policy_id) {
                    $io->verbose("Incident #{$incident->id}: skipped (no escalation policy on monitor)");
                    continue;
                }

                if ($dryRun) {
                    $io->out("DRY RUN: Would process escalation for incident #{$incident->id} (monitor: {$incident->monitor->name})");
                    continue;
                }

                $result = $this->escalationService->processEscalation($incident);

                if ($result !== null) {
                    $escalated++;
                    $io->verbose("Incident #{$incident->id}: {$result}");
                    Log::info("Escalation: Incident #{$incident->id} — {$result}");
                } else {
                    $io->verbose("Incident #{$incident->id}: no action needed");
                }
            }

            $elapsed = round((microtime(true) - $startTime) * 1000);
            $summary = "Escalation check complete: {$processed} checked, {$escalated} escalated ({$elapsed}ms)";

            $io->verbose($summary);
            Log::info($summary);

            if ($escalated > 0) {
                $io->out($summary);
            }

            return static::CODE_SUCCESS;
        } catch (\Exception $e) {
            $io->error("Escalation check failed: {$e->getMessage()}");
            Log::error("Escalation check failed: {$e->getMessage()}");

            return static::CODE_ERROR;
        }
    }
}
