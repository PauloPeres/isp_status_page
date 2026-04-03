<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * EscalationPoliciesController (TASK-NG-007)
 *
 * CRUD for escalation policies within the current organization.
 */
class EscalationPoliciesController extends AppController
{
    /**
     * GET /api/v2/escalation-policies
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('EscalationPolicies');
        $policies = $table->find()
            ->where(['EscalationPolicies.organization_id' => $this->currentOrgId])
            ->orderBy(['EscalationPolicies.name' => 'ASC'])
            ->all();

        $this->success(['escalation_policies' => $policies->toArray()]);
    }

    /**
     * GET /api/v2/escalation-policies/{id}
     *
     * @param string $id Escalation policy ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $policy = $this->resolveOrgEntity('EscalationPolicies', $id);

        if (!$policy) {
            $this->error('Escalation policy not found', 404);

            return;
        }

        $this->success(['escalation_policy' => $policy]);
    }

    /**
     * POST /api/v2/escalation-policies
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('EscalationPolicies');
        $policy = $table->newEntity($this->request->getData());
        $policy->set('organization_id', $this->currentOrgId);

        if (!$table->save($policy)) {
            $this->error('Validation failed', 422, $policy->getErrors());

            return;
        }

        $this->success(['escalation_policy' => $policy], 201);
    }

    /**
     * PUT /api/v2/escalation-policies/{id}
     *
     * @param string $id Escalation policy ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('EscalationPolicies');
        $policy = $this->resolveOrgEntity('EscalationPolicies', $id);

        if (!$policy) {
            $this->error('Escalation policy not found', 404);

            return;
        }

        $policy = $table->patchEntity($policy, $this->request->getData());
        if (!$table->save($policy)) {
            $this->error('Validation failed', 422, $policy->getErrors());

            return;
        }

        $this->success(['escalation_policy' => $policy]);
    }

    /**
     * DELETE /api/v2/escalation-policies/{id}
     *
     * @param string $id Escalation policy ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('EscalationPolicies');
        $policy = $this->resolveOrgEntity('EscalationPolicies', $id);

        if (!$policy) {
            $this->error('Escalation policy not found', 404);

            return;
        }

        if (!$table->delete($policy)) {
            $this->error('Failed to delete escalation policy', 500);

            return;
        }

        $this->success(['message' => 'Escalation policy deleted']);
    }
}
