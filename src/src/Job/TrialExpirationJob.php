<?php
declare(strict_types=1);

namespace App\Job;

use App\Service\PlanService;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;

/**
 * Trial Expiration Job
 *
 * Processes expired trials: pauses excess monitors and sends
 * notification emails to org owners. Pushed by the Scheduler
 * once per day.
 */
class TrialExpirationJob implements JobInterface
{
    use LocatorAwareTrait;

    public static bool $shouldBeUnique = true;
    public static ?int $maxAttempts = 2;

    public function execute(Message $message): ?string
    {
        Log::info('TrialExpirationJob: processing expired trials');

        $orgsTable = $this->fetchTable('Organizations');
        $monitorsTable = $this->fetchTable('Monitors');
        $planService = new PlanService();

        // Find orgs with expired trials (no Stripe subscription)
        $expiredOrgs = $orgsTable->find()
            ->where([
                'trial_ends_at IS NOT' => null,
                'trial_ends_at <=' => DateTime::now(),
                'OR' => [
                    'stripe_subscription_id IS' => null,
                    'stripe_subscription_id' => '',
                ],
                'active' => true,
            ])
            ->all();

        $processed = 0;
        $monitorsPaused = 0;

        foreach ($expiredOrgs as $org) {
            // Get the free plan limit
            $freePlan = $this->fetchTable('Plans')->find()
                ->where(['slug' => 'free'])
                ->first();

            if (!$freePlan) {
                continue;
            }

            $monitorLimit = $freePlan->monitor_limit;

            // Count active monitors
            $activeMonitors = $monitorsTable->find()
                ->where([
                    'organization_id' => $org->id,
                    'active' => true,
                ])
                ->orderBy(['created' => 'ASC'])
                ->all()
                ->toArray();

            if (count($activeMonitors) > $monitorLimit) {
                // Pause excess monitors (keep oldest ones active)
                $toKeep = array_slice($activeMonitors, 0, $monitorLimit);
                $toPause = array_slice($activeMonitors, $monitorLimit);

                foreach ($toPause as $monitor) {
                    $monitor->set('active', false);
                    $monitor->set('pause_reason', 'trial_expired');
                    $monitorsTable->save($monitor);
                    $monitorsPaused++;
                }
            }

            // Send notification email to org owner
            try {
                $ownerLink = $this->fetchTable('OrganizationUsers')->find()
                    ->where([
                        'organization_id' => $org->id,
                        'role' => 'owner',
                    ])
                    ->contain(['Users'])
                    ->first();

                if ($ownerLink && $ownerLink->user) {
                    $emailService = new \App\Service\EmailService();
                    $emailService->sendTrialExpired($ownerLink->user, $org);
                }
            } catch (\Exception $e) {
                Log::warning("TrialExpirationJob: failed to send email for org {$org->id}: {$e->getMessage()}");
            }

            $processed++;
        }

        Log::info("TrialExpirationJob: processed {$processed} orgs, paused {$monitorsPaused} monitors");

        return Processor::ACK;
    }
}
