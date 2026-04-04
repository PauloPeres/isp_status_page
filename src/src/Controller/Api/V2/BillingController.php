<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * BillingController (TASK-NG-009)
 *
 * Billing plans, checkout, portal, usage, and credits.
 */
class BillingController extends AppController
{
    protected \App\Service\BillingService $billingService;

    public function initialize(): void
    {
        parent::initialize();
        $this->billingService = new \App\Service\BillingService();
    }

    /**
     * GET /api/v2/billing/plans
     *
     * List available billing plans.
     *
     * @return void
     */
    public function plans(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = $this->billingService;
            $plans = $service->getPlans();

            // Include current plan slug for the authenticated organization
            $currentPlan = 'free';
            if ($this->currentOrgId > 0) {
                $orgsTable = $this->fetchTable('Organizations');
                $org = $orgsTable->find()
                    ->where(['Organizations.id' => $this->currentOrgId])
                    ->first();
                if ($org && !empty($org->plan)) {
                    $currentPlan = $org->plan;
                }
            }

            $this->success(['plans' => $plans, 'current_plan' => $currentPlan]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch plans: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/billing/checkout
     *
     * Create a Stripe checkout session for a plan.
     *
     * @return void
     */
    public function checkout(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner'])) {
            return;
        }

        $planSlug = $this->request->getData('plan');
        if (empty($planSlug)) {
            $this->error('Plan is required', 400);

            return;
        }

        try {
            $service = $this->billingService;
            $session = $service->createCheckoutSession($this->currentOrgId, $planSlug);

            if (empty($session)) {
                $this->error('Stripe is not configured. Contact support to upgrade your plan.', 422);

                return;
            }

            $this->success(['checkout_url' => $session]);
        } catch (\Exception $e) {
            $this->error('Failed to create checkout session: ' . $e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v2/billing/portal
     *
     * Create a Stripe customer portal session.
     *
     * @return void
     */
    public function portal(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner'])) {
            return;
        }

        try {
            $service = $this->billingService;
            $url = $service->createPortalSession($this->currentOrgId);

            $this->success(['portal_url' => $url]);
        } catch (\Exception $e) {
            $this->error('Failed to create portal session: ' . $e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v2/billing/usage
     *
     * Return current usage metrics for the organization.
     *
     * @return void
     */
    public function usage(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $service = $this->billingService;
            $usage = $service->getUsage($this->currentOrgId);

            $this->success(['usage' => $usage]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch usage: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/billing/credits
     *
     * Return credit balance for the organization.
     *
     * @return void
     */
    public function credits(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $service = $this->billingService;
            $credits = $service->getCredits($this->currentOrgId);

            $this->success(['credits' => $credits]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch credits: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/billing/credit-usage
     *
     * Return credit usage data with transactions, summary, and daily breakdown.
     *
     * Query params: from, to, channel, page, limit
     *
     * @return void
     */
    public function creditUsage(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $creditService = new \App\Service\Billing\NotificationCreditService();
            $credits = $creditService->getCredits($this->currentOrgId);

            $transactionsTable = $this->fetchTable('NotificationCreditTransactions');

            $page = max(1, (int)$this->request->getQuery('page', 1));
            $limit = min(100, max(1, (int)$this->request->getQuery('limit', 25)));
            $channel = $this->request->getQuery('channel', '');
            $from = $this->request->getQuery('from', '');
            $to = $this->request->getQuery('to', '');

            // Build transaction query
            $conditions = ['organization_id' => $this->currentOrgId];
            if (!empty($channel)) {
                $conditions['channel'] = $channel;
            }
            if (!empty($from)) {
                $conditions['created >='] = $from;
            }
            if (!empty($to)) {
                $conditions['created <='] = $to . ' 23:59:59';
            }

            $total = $transactionsTable->find()
                ->where($conditions)
                ->count();

            $transactions = $transactionsTable->find()
                ->where($conditions)
                ->orderBy(['created' => 'DESC'])
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->all()
                ->toArray();

            // Format transactions
            $formattedTransactions = [];
            foreach ($transactions as $tx) {
                $formattedTransactions[] = [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'balance_after' => $tx->balance_after,
                    'channel' => $tx->channel,
                    'description' => $tx->description,
                    'reference_id' => $tx->reference_id,
                    'created' => $tx->created ? $tx->created->format('Y-m-d H:i:s') : null,
                ];
            }

            // Summary: last 30 days
            $thirtyDaysAgo = \Cake\I18n\DateTime::now()->subDays(30);

            $usageTransactions = $transactionsTable->find()
                ->where([
                    'organization_id' => $this->currentOrgId,
                    'type' => 'usage',
                    'created >=' => $thirtyDaysAgo,
                ])
                ->all();

            $totalUsed30d = 0;
            $byChannel = ['sms' => 0, 'whatsapp' => 0, 'voice_call' => 0];
            $dailyUsage = [];

            foreach ($usageTransactions as $tx) {
                $amount = abs($tx->amount);
                $totalUsed30d += $amount;
                $ch = $tx->channel ?: 'unknown';
                if (isset($byChannel[$ch])) {
                    $byChannel[$ch] += $amount;
                }
                $day = $tx->created ? $tx->created->format('Y-m-d') : 'unknown';
                if (!isset($dailyUsage[$day])) {
                    $dailyUsage[$day] = 0;
                }
                $dailyUsage[$day] += $amount;
            }

            // Fill in missing days in the last 30 days
            $dailyUsageFormatted = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = \Cake\I18n\DateTime::now()->subDays($i)->format('Y-m-d');
                $dailyUsageFormatted[] = [
                    'date' => $date,
                    'credits' => $dailyUsage[$date] ?? 0,
                ];
            }

            // Top consumers (monitors using most credits)
            $topConsumers = $transactionsTable->find()
                ->select([
                    'description',
                    'channel',
                    'total_credits' => $transactionsTable->find()->func()->sum(
                        $transactionsTable->find()->func()->abs(['amount' => 'identifier'])
                    ),
                    'count' => $transactionsTable->find()->func()->count('*'),
                ])
                ->where([
                    'organization_id' => $this->currentOrgId,
                    'type' => 'usage',
                    'created >=' => $thirtyDaysAgo,
                ])
                ->groupBy(['description', 'channel'])
                ->orderBy(['total_credits' => 'DESC'])
                ->limit(10)
                ->all()
                ->toArray();

            $formattedTopConsumers = [];
            foreach ($topConsumers as $tc) {
                $formattedTopConsumers[] = [
                    'description' => $tc->description,
                    'channel' => $tc->channel,
                    'total_credits' => (int)$tc->total_credits,
                    'count' => (int)$tc->count,
                ];
            }

            // Monthly burn rate and projected depletion
            $daysInRange = max(1, min(30, (int)\Cake\I18n\DateTime::now()->diff($thirtyDaysAgo)->days));
            $avgPerDay = $daysInRange > 0 ? round($totalUsed30d / $daysInRange, 1) : 0;
            $projectedMonthly = round($avgPerDay * 30, 0);
            $depletionDays = $avgPerDay > 0 ? (int)ceil($credits->balance / $avgPerDay) : null;
            $depletionDate = $depletionDays !== null
                ? \Cake\I18n\DateTime::now()->addDays($depletionDays)->format('Y-m-d')
                : null;

            $this->success([
                'balance' => $credits->balance,
                'transactions' => $formattedTransactions,
                'summary' => [
                    'total_used_30d' => $totalUsed30d,
                    'by_channel' => $byChannel,
                    'daily_usage' => $dailyUsageFormatted,
                    'avg_per_day' => $avgPerDay,
                    'projected_monthly' => $projectedMonthly,
                    'depletion_date' => $depletionDate,
                    'top_consumers' => $formattedTopConsumers,
                    'channel_costs' => ['sms' => 1, 'whatsapp' => 1, 'voice_call' => 3],
                ],
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int)ceil($total / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch credit usage: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/billing/voice-call-logs
     *
     * Return voice call logs with status, DTMF, and duration data.
     *
     * Query params: page, limit
     *
     * @return void
     */
    public function voiceCallLogs(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');

            $page = max(1, (int)$this->request->getQuery('page', 1));
            $limit = min(100, max(1, (int)$this->request->getQuery('limit', 25)));

            $conditions = ['VoiceCallLogs.organization_id' => $this->currentOrgId];

            $total = $voiceCallLogsTable->find()
                ->where($conditions)
                ->count();

            $logs = $voiceCallLogsTable->find()
                ->where($conditions)
                ->orderBy(['VoiceCallLogs.created' => 'DESC'])
                ->limit($limit)
                ->offset(($page - 1) * $limit)
                ->all()
                ->toArray();

            $formattedLogs = [];
            foreach ($logs as $log) {
                // Mask phone number: +55••••9999
                $phone = $log->phone_number ?? '';
                $maskedPhone = $phone;
                if (strlen($phone) > 6) {
                    $maskedPhone = substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 7) . substr($phone, -4);
                }

                // Map DTMF result
                $dtmfResult = 'No input';
                if ($log->dtmf_input === '1') {
                    $dtmfResult = 'Acknowledged';
                } elseif ($log->dtmf_input === '2') {
                    $dtmfResult = 'Escalated';
                } elseif (!empty($log->dtmf_input)) {
                    $dtmfResult = 'Input: ' . $log->dtmf_input;
                }

                $formattedLogs[] = [
                    'id' => $log->id,
                    'public_id' => $log->public_id,
                    'phone_number' => $maskedPhone,
                    'status' => $log->status ?? 'unknown',
                    'dtmf_input' => $log->dtmf_input,
                    'dtmf_result' => $dtmfResult,
                    'duration_seconds' => $log->duration_seconds,
                    'cost_credits' => $log->cost_credits,
                    'tts_language' => $log->tts_language,
                    'sip_provider' => $log->sip_provider,
                    'escalation_position' => $log->escalation_position,
                    'monitor_id' => $log->monitor_id,
                    'incident_id' => $log->incident_id,
                    'created' => $log->created ? $log->created->format('Y-m-d H:i:s') : null,
                ];
            }

            $this->success([
                'voice_call_logs' => $formattedLogs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int)ceil($total / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch voice call logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/billing/auto-replenish
     *
     * Returns current auto-replenish settings for the organization.
     *
     * @return void
     */
    public function autoReplenish(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $creditService = new \App\Service\Billing\NotificationCreditService();
            $settings = $creditService->getAutoReplenishSettings($this->currentOrgId);

            $this->success(['auto_replenish' => $settings]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch auto-replenish settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/v2/billing/auto-replenish
     *
     * Update auto-replenish settings (enable/disable, threshold, amount, max monthly).
     *
     * @return void
     */
    public function updateAutoReplenish(): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner'])) {
            return;
        }

        $data = $this->request->getData();
        $settings = [];

        if (array_key_exists('enabled', $data)) {
            $settings['enabled'] = $data['enabled'];
        }
        if (array_key_exists('threshold', $data)) {
            $threshold = (int)$data['threshold'];
            if ($threshold < 1 || $threshold > 10000) {
                $this->error('Threshold must be between 1 and 10,000', 400);

                return;
            }
            $settings['threshold'] = $threshold;
        }
        if (array_key_exists('amount', $data)) {
            $amount = (int)$data['amount'];
            if ($amount < 100 || $amount > 10000) {
                $this->error('Amount must be between 100 and 10,000', 400);

                return;
            }
            $settings['amount'] = $amount;
        }
        if (array_key_exists('max_monthly', $data)) {
            $maxMonthly = (int)$data['max_monthly'];
            if ($maxMonthly < 100 || $maxMonthly > 100000) {
                $this->error('Monthly cap must be between 100 and 100,000', 400);

                return;
            }
            $settings['max_monthly'] = $maxMonthly;
        }

        if (empty($settings)) {
            $this->error('No settings provided to update', 400);

            return;
        }

        try {
            $creditService = new \App\Service\Billing\NotificationCreditService();
            $result = $creditService->updateAutoReplenishSettings($this->currentOrgId, $settings);

            if (!$result) {
                $this->error('Failed to save auto-replenish settings', 500);

                return;
            }

            $updatedSettings = $creditService->getAutoReplenishSettings($this->currentOrgId);
            $this->success(['auto_replenish' => $updatedSettings]);
        } catch (\Exception $e) {
            $this->error('Failed to update auto-replenish settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/billing/credits/buy
     *
     * Create a Stripe checkout session for purchasing notification credits.
     *
     * @return void
     */
    public function buyCredits(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner'])) {
            return;
        }

        $amount = (int)$this->request->getData('amount', 100);
        if ($amount <= 0 || $amount > 10000) {
            $this->error('Amount must be between 1 and 10,000', 400);

            return;
        }

        try {
            $creditService = new \App\Service\Billing\NotificationCreditService();
            $checkoutUrl = $creditService->purchaseCredits($this->currentOrgId, $amount);

            if ($checkoutUrl === null) {
                $this->error('Stripe is not configured. Contact support to purchase credits.', 422);

                return;
            }

            $this->success(['checkout_url' => $checkoutUrl]);
        } catch (\Exception $e) {
            $this->error('Failed to create credit purchase session: ' . $e->getMessage(), 422);
        }
    }
}
