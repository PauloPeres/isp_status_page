<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\PlanService;

/**
 * AlertRulesController (TASK-NG-007)
 *
 * CRUD for alert rules within the current organization.
 */
class AlertRulesController extends AppController
{
    /**
     * GET /api/v2/alert-rules
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('AlertRules');
        $rules = $table->find()
            ->where(['AlertRules.organization_id' => $this->currentOrgId])
            ->orderBy(['AlertRules.created' => 'DESC'])
            ->all();

        $this->success(['alert_rules' => $rules->toArray()]);
    }

    /**
     * GET /api/v2/alert-rules/{id}
     *
     * @param string $id Alert rule ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('AlertRules');
        $rule = $table->find()
            ->where([
                'AlertRules.id' => $id,
                'AlertRules.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$rule) {
            $this->error('Alert rule not found', 404);

            return;
        }

        $this->success(['alert_rule' => $rule]);
    }

    /**
     * POST /api/v2/alert-rules
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $planService = new PlanService();
        $channel = $this->request->getData('channel') ?? 'email';
        $featureMap = [
            'slack' => 'slack_alerts', 'discord' => 'slack_alerts',
            'telegram' => 'all_alert_channels', 'sms' => 'all_alert_channels',
            'whatsapp' => 'all_alert_channels', 'pagerduty' => 'all_alert_channels',
            'opsgenie' => 'all_alert_channels', 'webhook' => 'webhook_alerts',
        ];
        $requiredFeature = $featureMap[$channel] ?? null;
        if ($requiredFeature) {
            $check = $planService->checkFeature($this->currentOrgId, $requiredFeature);
            if (!$check['allowed']) {
                $this->planLimitError("{$channel} alerts are not available on your {$check['plan_name']} plan. Upgrade to use this channel.", $check);
                return;
            }
        }

        $table = $this->fetchTable('AlertRules');
        $rule = $table->newEntity($this->request->getData());
        $rule->set('organization_id', $this->currentOrgId);

        if (!$table->save($rule)) {
            $this->error('Validation failed', 422, $rule->getErrors());

            return;
        }

        $this->success(['alert_rule' => $rule], 201);
    }

    /**
     * PUT /api/v2/alert-rules/{id}
     *
     * @param string $id Alert rule ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('AlertRules');
        $rule = $table->find()
            ->where([
                'AlertRules.id' => $id,
                'AlertRules.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$rule) {
            $this->error('Alert rule not found', 404);

            return;
        }

        $rule = $table->patchEntity($rule, $this->request->getData());
        if (!$table->save($rule)) {
            $this->error('Validation failed', 422, $rule->getErrors());

            return;
        }

        $this->success(['alert_rule' => $rule]);
    }

    /**
     * DELETE /api/v2/alert-rules/{id}
     *
     * @param string $id Alert rule ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('AlertRules');
        $rule = $table->find()
            ->where([
                'AlertRules.id' => $id,
                'AlertRules.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$rule) {
            $this->error('Alert rule not found', 404);

            return;
        }

        if (!$table->delete($rule)) {
            $this->error('Failed to delete alert rule', 500);

            return;
        }

        $this->success(['message' => 'Alert rule deleted']);
    }
}
