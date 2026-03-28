<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Billing\NotificationCreditService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Grant Monthly Credits Command
 *
 * Grants monthly notification credits to all active Pro and Business organizations.
 * Should be run on the 1st of each month via cron:
 *   0 0 1 * * www-data cd /var/www/html && bin/cake grant_monthly_credits
 *
 * Idempotent: skips organizations that have already received their grant this month.
 */
class GrantMonthlyCreditsCommand extends Command
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
            ->setDescription('Grant monthly notification credits to all eligible organizations.')
            ->addOption('dry-run', [
                'short' => 'd',
                'boolean' => true,
                'default' => false,
                'help' => 'Show what would be granted without making changes.',
            ]);

        return $parser;
    }

    /**
     * Execute the command.
     *
     * Iterates over all active, paid organizations and grants monthly credits
     * if they haven't already been granted this month.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int Exit code.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $dryRun = (bool)$args->getOption('dry-run');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No credits will be granted.');
        }

        $creditService = new NotificationCreditService();
        $orgsTable = $this->fetchTable('Organizations');

        $orgs = $orgsTable->find()
            ->where(['active' => true, 'plan !=' => 'free'])
            ->all();

        $totalOrgs = $orgs->count();
        $io->info("Found {$totalOrgs} eligible organizations (active, non-free plan).");

        $granted = 0;
        $skipped = 0;

        foreach ($orgs as $org) {
            // Check if already granted this month
            $credits = $creditService->getCredits($org->id);

            if ($credits->last_grant_at && $credits->last_grant_at->isThisMonth()) {
                $skipped++;
                $io->verbose("Skipped {$org->name} (ID: {$org->id}) - already granted this month.");
                continue;
            }

            $expectedAmount = $creditService->getGrantForPlan($org->plan);

            if ($expectedAmount <= 0) {
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $io->info("Would grant {$expectedAmount} credits to {$org->name} (ID: {$org->id}, plan: {$org->plan})");
                $granted++;
                continue;
            }

            $amount = $creditService->grantMonthlyCredits($org->id);

            if ($amount > 0) {
                $granted++;
                $io->info("Granted {$amount} credits to {$org->name} (ID: {$org->id}, plan: {$org->plan})");
            }
        }

        $io->hr();
        $io->success("Monthly credits granted to {$granted} organizations.");

        if ($skipped > 0) {
            $io->info("Skipped {$skipped} organizations (already granted or not eligible).");
        }

        return static::CODE_SUCCESS;
    }
}
