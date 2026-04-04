<?php
declare(strict_types=1);

namespace App\Service\Billing;

use App\Job\AutoReplenishCheckJob;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\QueueManager;

/**
 * Notification Credit Service
 *
 * Manages notification credit balances, transactions, monthly grants,
 * and purchase flows for paid notification channels (SMS, WhatsApp).
 */
class NotificationCreditService
{
    use LocatorAwareTrait;

    /**
     * Monthly credit grants by plan.
     *
     * @var array<string, int>
     */
    private const PLAN_GRANTS = [
        'free' => 0,
        'pro' => 50,
        'business' => 200,
    ];

    /**
     * Price per credit pack in cents.
     *
     * @var int
     */
    private const CREDIT_PACK_PRICE_CENTS = 500; // $5

    /**
     * Credits per pack.
     *
     * @var int
     */
    private const CREDITS_PER_PACK = 100;

    /**
     * Get or create credit record for an organization.
     *
     * @param int $orgId Organization ID
     * @return \Cake\Datasource\EntityInterface
     */
    public function getCredits(int $orgId)
    {
        $creditsTable = $this->fetchTable('NotificationCredits');

        $credits = $creditsTable->find()
            ->where(['organization_id' => $orgId])
            ->first();

        if (!$credits) {
            $org = $this->fetchTable('Organizations')->get($orgId);
            $monthlyGrant = self::PLAN_GRANTS[$org->plan] ?? 0;

            $credits = $creditsTable->newEntity([
                'organization_id' => $orgId,
                'balance' => 0,
                'monthly_grant' => $monthlyGrant,
                'auto_recharge' => false,
                'auto_recharge_threshold' => 10,
                'auto_recharge_amount' => 100,
            ]);
            $creditsTable->save($credits);
        }

        return $credits;
    }

    /**
     * Add credits to an organization's balance.
     *
     * @param int $orgId Organization ID
     * @param int $amount Number of credits to add
     * @param string $type Transaction type (purchase, monthly_grant, manual_adjustment, refund)
     * @param string|null $description Human-readable description
     * @param string|null $referenceId External reference (Stripe payment ID, etc.)
     * @return bool
     */
    public function addCredits(int $orgId, int $amount, string $type, ?string $description = null, ?string $referenceId = null): bool
    {
        $creditsTable = $this->fetchTable('NotificationCredits');
        $transactionsTable = $this->fetchTable('NotificationCreditTransactions');

        $credits = $this->getCredits($orgId);
        $newBalance = $credits->balance + $amount;

        $credits->balance = $newBalance;
        if ($type === 'monthly_grant') {
            $credits->last_grant_at = DateTime::now();
        }
        $credits->modified = DateTime::now();

        if (!$creditsTable->save($credits)) {
            Log::error("Failed to add {$amount} credits to org {$orgId}");

            return false;
        }

        $transaction = $transactionsTable->newEntity([
            'organization_id' => $orgId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description,
            'reference_id' => $referenceId,
        ]);
        $transactionsTable->save($transaction);

        return true;
    }

    /**
     * Deduct credits for a notification send.
     *
     * @param int $orgId Organization ID
     * @param int $amount Credits to deduct
     * @param string $channel Notification channel (sms, whatsapp)
     * @param string|null $description Description of the usage
     * @return bool True if deduction succeeded, false if insufficient balance
     */
    public function deductCredits(int $orgId, int $amount, string $channel, ?string $description = null): bool
    {
        $credits = $this->getCredits($orgId);

        if ($credits->balance < $amount) {
            return false;
        }

        $creditsTable = $this->fetchTable('NotificationCredits');
        $transactionsTable = $this->fetchTable('NotificationCreditTransactions');

        $newBalance = $credits->balance - $amount;
        $credits->balance = $newBalance;
        $credits->modified = DateTime::now();

        if (!$creditsTable->save($credits)) {
            return false;
        }

        $transaction = $transactionsTable->newEntity([
            'organization_id' => $orgId,
            'type' => 'usage',
            'amount' => -$amount,
            'balance_after' => $newBalance,
            'channel' => $channel,
            'description' => $description ?? "Sent {$channel} notification",
        ]);
        $transactionsTable->save($transaction);

        // Push auto-replenish check to queue so it doesn't block the notification flow
        try {
            QueueManager::push(AutoReplenishCheckJob::class, [
                'data' => ['organization_id' => $orgId],
            ], ['config' => 'default']);
        } catch (\Exception $e) {
            // Queue push failure should never block credit deduction
            Log::warning("Failed to push AutoReplenishCheckJob for org {$orgId}: {$e->getMessage()}");
        }

        return true;
    }

    /**
     * Check if an organization has enough credits.
     *
     * @param int $orgId Organization ID
     * @param int $amount Required credits
     * @return bool
     */
    public function hasCredits(int $orgId, int $amount = 1): bool
    {
        $credits = $this->getCredits($orgId);

        return $credits->balance >= $amount;
    }

    /**
     * Grant monthly credits to an organization based on its plan.
     *
     * @param int $orgId Organization ID
     * @return int Number of credits granted (0 if not eligible)
     */
    public function grantMonthlyCredits(int $orgId): int
    {
        $org = $this->fetchTable('Organizations')->get($orgId);
        $amount = self::PLAN_GRANTS[$org->plan] ?? 0;

        if ($amount <= 0) {
            return 0;
        }

        $description = sprintf('Monthly grant for %s plan (%d credits)', ucfirst($org->plan), $amount);
        $this->addCredits($orgId, $amount, 'monthly_grant', $description);

        // Update the monthly_grant field in case plan changed
        $creditsTable = $this->fetchTable('NotificationCredits');
        $credits = $this->getCredits($orgId);
        $credits->monthly_grant = $amount;
        $creditsTable->save($credits);

        return $amount;
    }

    /**
     * Create a Stripe checkout session for purchasing credits.
     *
     * @param int $orgId Organization ID
     * @param int $amount Number of credits to purchase
     * @return string|null Checkout URL or null on failure
     */
    public function purchaseCredits(int $orgId, int $amount = 100): ?string
    {
        $packs = (int)ceil($amount / self::CREDITS_PER_PACK);
        $totalCredits = $packs * self::CREDITS_PER_PACK;
        $totalCents = $packs * self::CREDIT_PACK_PRICE_CENTS;

        $stripeService = new StripeService();
        if (!$stripeService->isConfigured()) {
            Log::warning("Stripe not configured, cannot purchase credits for org {$orgId}");

            return null;
        }

        try {
            $customerId = $stripeService->createCustomer($orgId);
            if (!$customerId) {
                return null;
            }

            $session = \Stripe\Checkout\Session::create([
                'customer' => $customerId,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => sprintf('%d Notification Credits', $totalCredits),
                            'description' => 'Credits for SMS and WhatsApp notifications',
                        ],
                        'unit_amount' => $totalCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => \Cake\Routing\Router::url(['controller' => 'Billing', 'action' => 'plans'], true) . '?credits_purchased=' . $totalCredits,
                'cancel_url' => \Cake\Routing\Router::url(['controller' => 'Billing', 'action' => 'plans'], true),
                'metadata' => [
                    'type' => 'credit_purchase',
                    'organization_id' => $orgId,
                    'credits' => $totalCredits,
                ],
            ]);

            return $session->url;
        } catch (\Exception $e) {
            Log::error("Failed to create credit purchase session: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Get usage stats for the current month.
     *
     * @param int $orgId Organization ID
     * @return array{used: int, purchased: int, granted: int}
     */
    public function getMonthlyUsage(int $orgId): array
    {
        $transactionsTable = $this->fetchTable('NotificationCreditTransactions');
        $startOfMonth = DateTime::now()->startOfMonth();

        $transactions = $transactionsTable->find()
            ->where([
                'organization_id' => $orgId,
                'created >=' => $startOfMonth,
            ])
            ->all();

        $used = 0;
        $purchased = 0;
        $granted = 0;

        foreach ($transactions as $tx) {
            switch ($tx->type) {
                case 'usage':
                    $used += abs($tx->amount);
                    break;
                case 'purchase':
                    $purchased += $tx->amount;
                    break;
                case 'monthly_grant':
                    $granted += $tx->amount;
                    break;
                case 'auto_replenish':
                    $purchased += $tx->amount;
                    break;
            }
        }

        return compact('used', 'purchased', 'granted');
    }

    /**
     * Get the total credits auto-replenished this calendar month.
     *
     * @param int $orgId Organization ID
     * @return int Total credits auto-replenished this month
     */
    public function getMonthlyAutoReplenishTotal(int $orgId): int
    {
        $transactionsTable = $this->fetchTable('NotificationCreditTransactions');
        $startOfMonth = DateTime::now()->startOfMonth();

        $result = $transactionsTable->find()
            ->select(['total' => $transactionsTable->find()->func()->sum('amount')])
            ->where([
                'organization_id' => $orgId,
                'type' => 'auto_replenish',
                'created >=' => $startOfMonth,
            ])
            ->disableAutoFields()
            ->first();

        return (int)($result->total ?? 0);
    }

    /**
     * Check if auto-replenish should fire and charge the customer if so.
     *
     * Called asynchronously via AutoReplenishCheckJob after each credit deduction.
     * Checks balance against threshold, verifies monthly cap, then charges via Stripe.
     *
     * @param int $orgId Organization ID
     * @return bool True if credits were auto-replenished, false otherwise
     */
    public function checkAndAutoReplenish(int $orgId): bool
    {
        $credits = $this->getCredits($orgId);

        // Check if auto-recharge is enabled
        if (!$credits->auto_recharge) {
            return false;
        }

        // Check if balance is below threshold
        if ($credits->balance >= $credits->auto_recharge_threshold) {
            return false;
        }

        $replenishAmount = $credits->auto_recharge_amount;
        $maxMonthly = $credits->auto_replenish_max_monthly ?? 500;

        // Check monthly cap
        $alreadyReplenished = $this->getMonthlyAutoReplenishTotal($orgId);
        if (($alreadyReplenished + $replenishAmount) > $maxMonthly) {
            Log::info(
                "Auto-replenish skipped for org {$orgId}: monthly cap would be exceeded. " .
                "Already replenished: {$alreadyReplenished}, requested: {$replenishAmount}, cap: {$maxMonthly}"
            );

            return false;
        }

        // Get the organization's Stripe customer ID
        $org = $this->fetchTable('Organizations')->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$org || empty($org->stripe_customer_id)) {
            Log::warning("Auto-replenish skipped for org {$orgId}: no Stripe customer ID on file");

            return false;
        }

        // Calculate price: consistent with manual purchase (100 credits = $5.00)
        $packs = (int)ceil($replenishAmount / self::CREDITS_PER_PACK);
        $totalCredits = $packs * self::CREDITS_PER_PACK;
        $totalCents = $packs * self::CREDIT_PACK_PRICE_CENTS;

        $stripeService = new StripeService();
        if (!$stripeService->isConfigured()) {
            Log::warning("Auto-replenish skipped for org {$orgId}: Stripe not configured");

            return false;
        }

        try {
            // Retrieve the customer's default payment method
            $stripe = new \Stripe\StripeClient((string)env('STRIPE_SECRET_KEY'));
            $customer = $stripe->customers->retrieve($org->stripe_customer_id, []);

            $defaultPaymentMethodId = $customer->invoice_settings->default_payment_method
                ?? $customer->default_source
                ?? null;

            if (empty($defaultPaymentMethodId)) {
                // Try to find any attached payment method
                $paymentMethods = $stripe->paymentMethods->all([
                    'customer' => $org->stripe_customer_id,
                    'type' => 'card',
                    'limit' => 1,
                ]);

                if (!empty($paymentMethods->data)) {
                    $defaultPaymentMethodId = $paymentMethods->data[0]->id;
                }
            }

            if (empty($defaultPaymentMethodId)) {
                Log::warning("Auto-replenish skipped for org {$orgId}: no payment method on file");

                return false;
            }

            // Create a PaymentIntent for the credit purchase
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $totalCents,
                'currency' => 'usd',
                'customer' => $org->stripe_customer_id,
                'payment_method' => $defaultPaymentMethodId,
                'off_session' => true,
                'confirm' => true,
                'metadata' => [
                    'org_id' => (string)$orgId,
                    'credits' => (string)$totalCredits,
                    'type' => 'auto_replenish',
                ],
                'description' => sprintf(
                    'Auto-replenish %d notification credits for %s',
                    $totalCredits,
                    $org->name
                ),
            ]);

            if ($paymentIntent->status !== 'succeeded') {
                Log::error(
                    "Auto-replenish payment not immediately successful for org {$orgId}. " .
                    "Status: {$paymentIntent->status}, PI: {$paymentIntent->id}"
                );

                return false;
            }

            // Payment succeeded — add credits
            $description = sprintf(
                'Auto-replenish: %d credits purchased ($%.2f)',
                $totalCredits,
                $totalCents / 100
            );
            $this->addCredits($orgId, $totalCredits, 'auto_replenish', $description, $paymentIntent->id);

            // Update the last charged timestamp
            $creditsTable = $this->fetchTable('NotificationCredits');
            $credits = $this->getCredits($orgId);
            $credits->auto_replenish_last_charged_at = DateTime::now();
            $creditsTable->save($credits);

            Log::info(
                "Auto-replenish successful for org {$orgId}: {$totalCredits} credits, " .
                "\${$totalCents} cents, PI: {$paymentIntent->id}"
            );

            return true;
        } catch (\Stripe\Exception\CardException $e) {
            Log::error(
                "Auto-replenish card declined for org {$orgId}: {$e->getMessage()} " .
                "(code: {$e->getStripeCode()}, decline_code: " . ($e->getDeclineCode() ?? 'n/a') . ')'
            );

            return false;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error("Auto-replenish Stripe API error for org {$orgId}: {$e->getMessage()}");

            return false;
        } catch (\Exception $e) {
            Log::error("Auto-replenish unexpected error for org {$orgId}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get auto-replenish settings for an organization.
     *
     * @param int $orgId Organization ID
     * @return array Auto-replenish settings
     */
    public function getAutoReplenishSettings(int $orgId): array
    {
        $credits = $this->getCredits($orgId);

        // Check if org has a payment method on file
        $org = $this->fetchTable('Organizations')->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        $hasPaymentMethod = false;
        if ($org && !empty($org->stripe_customer_id)) {
            try {
                $stripe = new \Stripe\StripeClient((string)env('STRIPE_SECRET_KEY'));
                $paymentMethods = $stripe->paymentMethods->all([
                    'customer' => $org->stripe_customer_id,
                    'type' => 'card',
                    'limit' => 1,
                ]);
                $hasPaymentMethod = !empty($paymentMethods->data);
            } catch (\Exception $e) {
                // Silently fail — just report no payment method
                Log::debug("Could not check payment methods for org {$orgId}: {$e->getMessage()}");
            }
        }

        // Get monthly auto-replenish spend
        $monthlyAutoReplenished = $this->getMonthlyAutoReplenishTotal($orgId);

        return [
            'enabled' => (bool)$credits->auto_recharge,
            'threshold' => (int)$credits->auto_recharge_threshold,
            'amount' => (int)$credits->auto_recharge_amount,
            'max_monthly' => (int)($credits->auto_replenish_max_monthly ?? 500),
            'last_charged_at' => $credits->auto_replenish_last_charged_at
                ? $credits->auto_replenish_last_charged_at->format('Y-m-d H:i:s')
                : null,
            'monthly_auto_replenished' => $monthlyAutoReplenished,
            'has_payment_method' => $hasPaymentMethod,
            'price_per_100_credits' => self::CREDIT_PACK_PRICE_CENTS, // cents
        ];
    }

    /**
     * Update auto-replenish settings for an organization.
     *
     * @param int $orgId Organization ID
     * @param array $settings Settings to update (enabled, threshold, amount, max_monthly)
     * @return bool True if save succeeded
     */
    public function updateAutoReplenishSettings(int $orgId, array $settings): bool
    {
        $creditsTable = $this->fetchTable('NotificationCredits');
        $credits = $this->getCredits($orgId);

        if (array_key_exists('enabled', $settings)) {
            $credits->auto_recharge = (bool)$settings['enabled'];
        }
        if (array_key_exists('threshold', $settings)) {
            $credits->auto_recharge_threshold = max(1, (int)$settings['threshold']);
        }
        if (array_key_exists('amount', $settings)) {
            // Enforce minimum of 100 credits per replenish (1 pack)
            $credits->auto_recharge_amount = max(100, (int)$settings['amount']);
        }
        if (array_key_exists('max_monthly', $settings)) {
            // Enforce minimum of 100 credits per month cap
            $credits->auto_replenish_max_monthly = max(100, (int)$settings['max_monthly']);
        }

        $credits->modified = DateTime::now();

        if (!$creditsTable->save($credits)) {
            Log::error("Failed to update auto-replenish settings for org {$orgId}");

            return false;
        }

        Log::info(
            "Updated auto-replenish settings for org {$orgId}: " .
            "enabled={$credits->auto_recharge}, threshold={$credits->auto_recharge_threshold}, " .
            "amount={$credits->auto_recharge_amount}, max_monthly={$credits->auto_replenish_max_monthly}"
        );

        return true;
    }

    /**
     * Credit costs per notification channel.
     *
     * @var array<string, int>
     */
    private const CHANNEL_COSTS = [
        'sms' => 1,
        'whatsapp' => 1,
        'voice_call' => 3,
    ];

    /**
     * Get the credit cost for a notification channel.
     *
     * @param string $channel The channel type (sms, whatsapp, voice_call)
     * @return int The number of credits required
     */
    public function getCostForChannel(string $channel): int
    {
        return self::CHANNEL_COSTS[$channel] ?? 1;
    }

    /**
     * Get the monthly grant amount for a plan.
     *
     * @param string $plan Plan slug
     * @return int
     */
    public function getGrantForPlan(string $plan): int
    {
        return self::PLAN_GRANTS[$plan] ?? 0;
    }

    /**
     * Get total credits across all organizations.
     *
     * @return array{total_balance: int, total_used_this_month: int, total_purchased_this_month: int}
     */
    public function getGlobalStats(): array
    {
        $creditsTable = $this->fetchTable('NotificationCredits');
        $transactionsTable = $this->fetchTable('NotificationCreditTransactions');
        $startOfMonth = DateTime::now()->startOfMonth();

        $totalBalance = (int)$creditsTable->find()
            ->select(['total' => $creditsTable->find()->func()->sum('balance')])
            ->disableAutoFields()
            ->first()
            ->total;

        $usedThisMonth = (int)$transactionsTable->find()
            ->select(['total' => $transactionsTable->find()->func()->sum(
                $transactionsTable->find()->func()->abs(['amount' => 'identifier'])
            )])
            ->where([
                'type' => 'usage',
                'created >=' => $startOfMonth,
            ])
            ->disableAutoFields()
            ->first()
            ->total;

        $purchasedThisMonth = (int)$transactionsTable->find()
            ->select(['total' => $transactionsTable->find()->func()->sum('amount')])
            ->where([
                'type' => 'purchase',
                'created >=' => $startOfMonth,
            ])
            ->disableAutoFields()
            ->first()
            ->total;

        return [
            'total_balance' => $totalBalance,
            'total_used_this_month' => $usedThisMonth,
            'total_purchased_this_month' => $purchasedThisMonth,
        ];
    }
}
