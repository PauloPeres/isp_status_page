<?php
declare(strict_types=1);

namespace App\Service\Billing;

use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

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
            }
        }

        return compact('used', 'purchased', 'granted');
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
