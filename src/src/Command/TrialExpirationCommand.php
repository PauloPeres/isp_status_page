<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Organization;
use App\Model\Entity\Plan;
use App\Service\PlanService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Trial Expiration Command
 *
 * Processes organizations whose free trial has expired.
 * Pauses monitors beyond the free plan limit and sends notification emails.
 *
 * Should be run daily via cron:
 *   0 4 * * * www-data cd /var/www/html && bin/cake trial_expiration
 *
 * Idempotent: only processes orgs that still have plan='free',
 * trial_ends_at <= NOW(), and no Stripe subscription.
 */
class TrialExpirationCommand extends Command
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
            ->setDescription('Process expired trials: pause excess monitors and notify owners.')
            ->addOption('dry-run', [
                'short' => 'd',
                'boolean' => true,
                'default' => false,
                'help' => 'Show what would happen without making changes.',
            ]);

        return $parser;
    }

    /**
     * Execute the command.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int Exit code.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $dryRun = (bool)$args->getOption('dry-run');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be made.');
        }

        $orgsTable = $this->fetchTable('Organizations');
        $monitorsTable = $this->fetchTable('Monitors');
        $planService = new PlanService();

        // Find organizations with expired trials:
        // - trial_ends_at is set and in the past
        // - no Stripe subscription (free tier)
        // - still active
        $now = DateTime::now();
        $expiredOrgs = $orgsTable->find()
            ->where([
                'Organizations.trial_ends_at IS NOT' => null,
                'Organizations.trial_ends_at <=' => $now,
                'Organizations.active' => true,
                'Organizations.stripe_subscription_id IS' => null,
            ])
            ->all();

        $totalExpired = $expiredOrgs->count();
        $io->info("Found {$totalExpired} organizations with expired trials.");

        if ($totalExpired === 0) {
            $io->success('No expired trials to process.');

            return static::CODE_SUCCESS;
        }

        // Get the free plan to know the monitor limit
        $plansTable = $this->fetchTable('Plans');
        $freePlan = $plansTable->find('bySlug', slug: Plan::SLUG_FREE)->first();

        if (!$freePlan) {
            $io->error('Free plan not found in database.');

            return static::CODE_ERROR;
        }

        $freeMonitorLimit = $freePlan->monitor_limit;
        $isUnlimited = $freePlan->isUnlimited('monitor_limit');
        $io->info("Free plan monitor limit: " . ($isUnlimited ? 'unlimited' : $freeMonitorLimit));

        $processed = 0;
        $monitorsPaused = 0;

        foreach ($expiredOrgs as $org) {
            $io->info("Processing org: {$org->name} (ID: {$org->id}, trial ended: {$org->trial_ends_at})");

            // Count active monitors for this org
            $activeMonitors = $monitorsTable->find()
                ->where([
                    'Monitors.organization_id' => $org->id,
                    'Monitors.active' => true,
                ])
                ->orderBy(['Monitors.created' => 'ASC']) // Keep oldest monitors active
                ->all()
                ->toArray();

            $activeCount = count($activeMonitors);

            if (!$isUnlimited && $activeCount > $freeMonitorLimit) {
                $excessCount = $activeCount - $freeMonitorLimit;
                $io->info("  Active monitors: {$activeCount}, limit: {$freeMonitorLimit}, pausing: {$excessCount}");

                // Pause the newest monitors (keep the oldest ones active)
                $monitorsToPause = array_slice($activeMonitors, $freeMonitorLimit);

                foreach ($monitorsToPause as $monitor) {
                    if ($dryRun) {
                        $io->info("  Would pause monitor: {$monitor->name} (ID: {$monitor->id})");
                    } else {
                        $monitor->active = false;
                        $monitor->pause_reason = 'trial_expired';
                        if ($monitorsTable->save($monitor)) {
                            $io->info("  Paused monitor: {$monitor->name} (ID: {$monitor->id})");
                            $monitorsPaused++;
                        } else {
                            $io->warning("  Failed to pause monitor: {$monitor->name} (ID: {$monitor->id})");
                        }
                    }
                }
            } else {
                $io->info("  Active monitors: {$activeCount} - within free plan limit, no pausing needed.");
            }

            // Send trial expiration email to the org owner
            if (!$dryRun) {
                $this->sendTrialExpiredEmail($org, $monitorsPaused > 0);
            }

            $processed++;
        }

        $io->hr();
        $io->success("Processed {$processed} expired trial organizations.");

        if ($monitorsPaused > 0) {
            $io->warning("Paused {$monitorsPaused} monitors that exceeded free plan limits.");
        }

        Log::info("TrialExpiration: processed {$processed} orgs, paused {$monitorsPaused} monitors");

        return static::CODE_SUCCESS;
    }

    /**
     * Send trial expiration email to the organization owner.
     *
     * @param \App\Model\Entity\Organization $org Organization entity
     * @param bool $monitorsPaused Whether monitors were paused
     * @return void
     */
    private function sendTrialExpiredEmail(Organization $org, bool $monitorsPaused): void
    {
        try {
            // Find the owner of this organization
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $owner = $orgUsersTable->find()
                ->contain(['Users'])
                ->where([
                    'OrganizationUsers.organization_id' => $org->id,
                    'OrganizationUsers.role' => 'owner',
                ])
                ->first();

            if (!$owner || !$owner->user) {
                Log::warning("No owner found for org {$org->id}, skipping trial expiry email.");

                return;
            }

            $emailService = new \App\Service\EmailService();
            $emailService->sendTrialExpired($owner->user, $org, $monitorsPaused);

            Log::info("Sent trial expiration email to {$owner->user->email} for org {$org->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send trial expiration email for org {$org->id}: " . $e->getMessage());
        }
    }
}
