<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use App\Controller\Api\V2\AppController;

/**
 * PlansController (D-02) — Super Admin
 *
 * CRUD for billing plans. Allows super admins to create
 * custom plan tiers for enterprise customers.
 */
class PlansController extends AppController
{
    /**
     * GET /api/v2/super-admin/plans
     *
     * List all plans (including inactive).
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('Plans');
        $plans = $table->find()
            ->orderBy(['Plans.display_order' => 'ASC', 'Plans.price_monthly' => 'ASC'])
            ->all()
            ->toArray();

        // Add usage counts per plan
        $orgsTable = $this->fetchTable('Organizations');
        foreach ($plans as &$plan) {
            $plan->organization_count = $orgsTable->find()
                ->where(['Organizations.plan' => $plan->slug])
                ->count();
        }

        $this->success([
            'items' => $plans,
            'pagination' => [
                'page' => 1,
                'limit' => count($plans),
                'total' => count($plans),
                'pages' => 1,
            ],
        ]);
    }

    /**
     * GET /api/v2/super-admin/plans/:id
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('Plans');

        try {
            $plan = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Plan not found', 404);
            return;
        }

        $orgsTable = $this->fetchTable('Organizations');
        $plan->organization_count = $orgsTable->find()
            ->where(['Organizations.plan' => $plan->slug])
            ->count();

        $this->success(['plan' => $plan]);
    }

    /**
     * POST /api/v2/super-admin/plans
     *
     * Create a new custom plan.
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('Plans');
        $data = $this->prepareData($this->request->getData());
        $plan = $table->newEntity($data);

        if (!$table->save($plan)) {
            $this->error('Validation failed', 422, $plan->getErrors());
            return;
        }

        $this->success(['plan' => $plan], 201);
    }

    /**
     * PUT /api/v2/super-admin/plans/:id
     *
     * Update a plan.
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('Plans');

        try {
            $plan = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Plan not found', 404);
            return;
        }

        $data = $this->prepareData($this->request->getData());
        $plan = $table->patchEntity($plan, $data);

        if (!$table->save($plan)) {
            $this->error('Validation failed', 422, $plan->getErrors());
            return;
        }

        $this->success(['plan' => $plan]);
    }

    /**
     * DELETE /api/v2/super-admin/plans/:id
     *
     * Delete a plan (only if no organizations are using it).
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('Plans');

        try {
            $plan = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Plan not found', 404);
            return;
        }

        // Prevent deletion of plans with active organizations
        $orgsTable = $this->fetchTable('Organizations');
        $orgCount = $orgsTable->find()
            ->where(['Organizations.plan' => $plan->slug])
            ->count();

        if ($orgCount > 0) {
            $this->error("Cannot delete plan \"{$plan->name}\" — {$orgCount} organization(s) are using it. Deactivate it instead.", 409);
            return;
        }

        if ($table->delete($plan)) {
            $this->success(['message' => 'Plan deleted']);
        } else {
            $this->error('Failed to delete plan', 500);
        }
    }

    /**
     * POST /api/v2/super-admin/plans/:id/duplicate
     *
     * Duplicate a plan (for quick custom plan creation).
     */
    public function duplicate(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('Plans');

        try {
            $source = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Plan not found', 404);
            return;
        }

        $data = $source->toArray();
        unset($data['id'], $data['created'], $data['modified']);
        $data['name'] = $data['name'] . ' (Copy)';
        $data['slug'] = $data['slug'] . '-copy-' . time();
        $data['active'] = false;
        $data['stripe_price_id_monthly'] = null;
        $data['stripe_price_id_yearly'] = null;

        $newPlan = $table->newEntity($data);

        if (!$table->save($newPlan)) {
            $this->error('Failed to duplicate plan', 422, $newPlan->getErrors());
            return;
        }

        $this->success(['plan' => $newPlan], 201);
    }

    /**
     * Prepare plan data for save — encode features array to JSON.
     */
    private function prepareData(array $data): array
    {
        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }
        return $data;
    }
}
