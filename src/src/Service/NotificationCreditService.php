<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\NotificationCredit;
use App\Model\Entity\NotificationCreditTransaction;
use App\Service\Billing\StripeService;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Notification Credit Service
 *
 * Manages notification credit balances for organizations. Paid channels
 * (SMS, WhatsApp) consume credits, while free channels (email, Slack,
 * Discord, Telegram, webhook) cost nothing.
 */
class NotificationCreditService
{
    use LocatorAwareTrait;

    /**
     * Credit cost per SMS message
     */
    public const CREDIT_COST_SMS = 1;

    /**
     * Credit cost per WhatsApp message
     */
    public const CREDIT_COST_WHATSAPP = 1;

    /**
     * Default threshold for low balance warning
     */
    public const LOW_BALANCE_THRESHOLD = 10;

    /**
     * Price in cents for 100 credits (Stripe)
     */
    public const CREDIT_PACK_PRICE_CENTS = 500;

    /**
     * Number of credits per purchase pack
     */
    public const CREDIT_PACK_SIZE = 100;

    /**
     * Get or create credit record for an organization
     *
     * @param int $orgId Organization ID
     * @return \App\Model\Entity\NotificationCredit
     */
    public function getCredits(int $orgId): NotificationCredit
    {
        $table = $this->fetchTable('NotificationCredits');

        $credits = $table->find()
            ->where(['NotificationCredits.organization_id' => $orgId])
            ->first();

        if ($credits !== null) {
            return $credits;
        }

        // Create a new credit record with zero balance
        $credits = $table->newEntity([
            'organization_id' => $orgId,
            'balance' => 0,
            'monthly_grant' => 0,
            'auto_recharge' => false,
            'auto_recharge_threshold' => 10,
            'auto_recharge_amount' => 100,
        ]);

        $saved = $table->save($credits);
        if (!$saved) {
            Log::error("Failed to create notification credit record for org {$orgId}");

            // Return the unsaved entity so callers don't break
            return $credits;
        }

        return $saved;
    }

    /**
     * Check if org has enough credits for a channel
     *
     * @param int $orgId Organization ID
     * @param string $channel Notification channel (sms, whatsapp, email, etc.)
     * @return bool
     */
    public function hasCredits(int $orgId, string $channel): bool
    {
        $cost = $this->getCostForChannel($channel);
        if ($cost === 0) {
            return true; // Free channels always pass
        }

        $credits = $this->getCredits($orgId);

        return $credits->balance >= $cost;
    }

    /**
     * Deduct credits for sending a message
     *
     * @param int $orgId Organization ID
     * @param string $channel Notification channel
     * @param string|null $referenceId External reference (alert_log ID, etc.)
     * @return bool True if deduction was successful
     */
    public function deduct(int $orgId, string $channel, ?string $referenceId = null): bool
    {
        $cost = $this->getCostForChannel($channel);
        if ($cost === 0) {
            return true;
        }

        $credits = $this->getCredits($orgId);
        if ($credits->balance < $cost) {
            Log::warning("Insufficient credits for org {$orgId}: balance={$credits->balance}, cost={$cost}, channel={$channel}");

            return false;
        }

        $credits->balance -= $cost;
        $table = $this->fetchTable('NotificationCredits');
        $saved = $table->save($credits);

        if (!$saved) {
            Log::error("Failed to save credit deduction for org {$orgId}");

            return false;
        }

        // Log transaction
        $this->logTransaction(
            $orgId,
            NotificationCreditTransaction::TYPE_USAGE,
            -$cost,
            $credits->balance,
            $channel,
            "Sent {$channel} notification",
            $referenceId
        );

        // Check low balance
        if ($credits->balance <= self::LOW_BALANCE_THRESHOLD) {
            $this->sendLowBalanceWarning($orgId, $credits);
        }

        return true;
    }

    /**
     * Add credits (purchase or grant)
     *
     * @param int $orgId Organization ID
     * @param int $amount Number of credits to add
     * @param string $type Transaction type (purchase, monthly_grant, manual_adjustment, refund)
     * @param string|null $description Human-readable description
     * @param string|null $referenceId External reference (Stripe payment ID, etc.)
     * @return bool True if credits were added successfully
     */
    public function addCredits(
        int $orgId,
        int $amount,
        string $type,
        ?string $description = null,
        ?string $referenceId = null,
    ): bool {
        if ($amount <= 0) {
            Log::warning("Attempted to add non-positive credits ({$amount}) for org {$orgId}");

            return false;
        }

        $credits = $this->getCredits($orgId);
        $credits->balance += $amount;

        $table = $this->fetchTable('NotificationCredits');
        $saved = $table->save($credits);

        if (!$saved) {
            Log::error("Failed to save credit addition for org {$orgId}");

            return false;
        }

        $this->logTransaction(
            $orgId,
            $type,
            $amount,
            $credits->balance,
            null,
            $description ?? "Added {$amount} credits ({$type})",
            $referenceId
        );

        Log::info("Added {$amount} credits to org {$orgId} (type: {$type}), new balance: {$credits->balance}");

        return true;
    }

    /**
     * Grant monthly credits based on plan
     *
     * @param int $orgId Organization ID
     * @return int Number of credits granted (0 if free plan)
     */
    public function grantMonthlyCredits(int $orgId): int
    {
        $planService = new PlanService();
        $plan = $planService->getPlanForOrganization($orgId);

        $grant = match ($plan->slug) {
            'pro' => 50,
            'business' => 200,
            default => 0,
        };

        if ($grant > 0) {
            $this->addCredits(
                $orgId,
                $grant,
                NotificationCreditTransaction::TYPE_MONTHLY_GRANT,
                "Monthly credit grant ({$plan->name} plan)"
            );

            $credits = $this->getCredits($orgId);
            $credits->monthly_grant = $grant;
            $credits->last_grant_at = DateTime::now();
            $this->fetchTable('NotificationCredits')->save($credits);
        }

        return $grant;
    }

    /**
     * Get cost for a channel (0 = free)
     *
     * @param string $channel Notification channel
     * @return int Credit cost per message
     */
    public function getCostForChannel(string $channel): int
    {
        return match ($channel) {
            'sms' => self::CREDIT_COST_SMS,
            'whatsapp' => self::CREDIT_COST_WHATSAPP,
            default => 0, // email, slack, discord, telegram, webhook = free
        };
    }

    /**
     * Purchase credits via Stripe (returns Stripe checkout session URL)
     *
     * @param int $orgId Organization ID
     * @param int $amount Number of credits to purchase (default 100)
     * @return string|null Stripe checkout session URL, or null on failure
     */
    public function purchaseCredits(int $orgId, int $amount = 100): ?string
    {
        $stripeService = new StripeService();

        if (!$stripeService->isConfigured()) {
            Log::warning("Stripe not configured, cannot purchase credits for org {$orgId}");

            return null;
        }

        $customerId = $stripeService->createCustomer($orgId);
        if (!$customerId) {
            Log::error("Failed to get/create Stripe customer for org {$orgId}");

            return null;
        }

        try {
            $packs = max(1, (int)ceil($amount / self::CREDIT_PACK_SIZE));
            $totalCredits = $packs * self::CREDIT_PACK_SIZE;
            $totalPriceCents = $packs * self::CREDIT_PACK_PRICE_CENTS;

            $session = \Stripe\Checkout\Session::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => "Notification Credits ({$totalCredits} credits)",
                            'description' => "Credits for SMS and WhatsApp notifications",
                        ],
                        'unit_amount' => $totalPriceCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'metadata' => [
                    'organization_id' => (string)$orgId,
                    'credit_amount' => (string)$totalCredits,
                    'type' => 'notification_credits',
                ],
                'success_url' => '/billing/credits?success=1',
                'cancel_url' => '/billing/credits?canceled=1',
            ]);

            Log::info("Created Stripe checkout session for {$totalCredits} credits, org {$orgId}");

            return $session->url;
        } catch (\Exception $e) {
            Log::error("Stripe checkout session creation failed for org {$orgId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Log a credit transaction
     *
     * @param int $orgId Organization ID
     * @param string $type Transaction type
     * @param int $amount Amount (positive or negative)
     * @param int $balanceAfter Balance after transaction
     * @param string|null $channel Notification channel (for usage type)
     * @param string|null $description Human-readable description
     * @param string|null $referenceId External reference
     * @return void
     */
    private function logTransaction(
        int $orgId,
        string $type,
        int $amount,
        int $balanceAfter,
        ?string $channel = null,
        ?string $description = null,
        ?string $referenceId = null,
    ): void {
        try {
            $table = $this->fetchTable('NotificationCreditTransactions');
            $transaction = $table->newEntity([
                'organization_id' => $orgId,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'channel' => $channel,
                'description' => $description,
                'reference_id' => $referenceId,
            ]);

            $saved = $table->save($transaction);
            if (!$saved) {
                Log::error("Failed to log credit transaction for org {$orgId}: " . json_encode($transaction->getErrors()));
            }
        } catch (\Exception $e) {
            Log::error("Exception logging credit transaction for org {$orgId}: {$e->getMessage()}");
        }
    }

    /**
     * Send low balance warning email to the organization owner
     *
     * Only sends once per 24-hour period to avoid spamming.
     *
     * @param int $orgId Organization ID
     * @param \App\Model\Entity\NotificationCredit $credits Credit entity
     * @return void
     */
    private function sendLowBalanceWarning(int $orgId, NotificationCredit $credits): void
    {
        // Only send once per 24 hours
        if (
            $credits->low_balance_notified_at !== null
            && $credits->low_balance_notified_at->wasWithinLast('24 hours')
        ) {
            return;
        }

        try {
            // Update the notification timestamp
            $credits->low_balance_notified_at = DateTime::now();
            $this->fetchTable('NotificationCredits')->save($credits);

            // Get organization owner email
            $orgUsersTable = $this->fetchTable('OrganizationUsers');
            $owner = $orgUsersTable->find()
                ->contain(['Users'])
                ->where([
                    'OrganizationUsers.organization_id' => $orgId,
                    'OrganizationUsers.role' => 'owner',
                ])
                ->first();

            if ($owner && isset($owner->user)) {
                $emailService = new EmailService();
                Log::info("Low balance warning sent for org {$orgId} to {$owner->user->email}, balance: {$credits->balance}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send low balance warning for org {$orgId}: {$e->getMessage()}");
        }
    }
}
