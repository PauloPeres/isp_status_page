<?php
declare(strict_types=1);

namespace App\Job;

use App\Service\Billing\NotificationCreditService;
use Cake\Log\Log;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;

/**
 * Auto Replenish Check Job
 *
 * Checks whether an organization's credit balance has dropped below
 * its auto-replenish threshold and, if so, charges their saved payment
 * method via Stripe and adds credits to the balance.
 *
 * Pushed to the queue after every credit deduction so the notification
 * flow is never blocked by Stripe API calls.
 */
class AutoReplenishCheckJob implements JobInterface
{
    /**
     * Maximum number of retry attempts.
     *
     * @var int|null
     */
    public static ?int $maxAttempts = 2;

    /**
     * Execute the auto-replenish check.
     *
     * @param \Cake\Queue\Job\Message $message The queue message
     * @return string Processor result (ACK or REJECT)
     */
    public function execute(Message $message): string
    {
        $data = $message->getArgument('data') ?? [];
        $orgId = $data['organization_id'] ?? null;

        if ($orgId === null) {
            Log::error('AutoReplenishCheckJob: Missing organization_id in message data');

            return Processor::REJECT;
        }

        try {
            $creditService = new NotificationCreditService();
            $result = $creditService->checkAndAutoReplenish((int)$orgId);

            if ($result) {
                Log::info("AutoReplenishCheckJob: Auto-replenished credits for org {$orgId}");
            }

            return Processor::ACK;
        } catch (\Exception $e) {
            Log::error("AutoReplenishCheckJob: Failed for org {$orgId}: {$e->getMessage()}");

            return Processor::REJECT;
        }
    }
}
