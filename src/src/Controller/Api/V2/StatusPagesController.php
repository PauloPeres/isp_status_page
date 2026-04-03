<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\PlanService;

/**
 * StatusPagesController (TASK-NG-012)
 *
 * CRUD for status page configurations.
 */
class StatusPagesController extends AppController
{
    /**
     * GET /api/v2/status-pages
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('StatusPages');
        $pages = $table->find()
            ->where(['StatusPages.organization_id' => $this->currentOrgId])
            ->orderBy(['StatusPages.name' => 'ASC'])
            ->all();

        $this->success(['status_pages' => $pages->toArray()]);
    }

    /**
     * GET /api/v2/status-pages/{id}
     *
     * @param string $id Status page ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('StatusPages');
        $page = $table->find()
            ->where([
                'StatusPages.id' => $id,
                'StatusPages.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$page) {
            $this->error('Status page not found', 404);

            return;
        }

        $this->success(['status_page' => $page]);
    }

    /**
     * POST /api/v2/status-pages
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
        $check = $planService->checkLimit($this->currentOrgId, 'status_page');
        if (!$check['allowed']) {
            $this->planLimitError("Status page limit reached. Your {$check['plan_name']} plan allows {$check['limit']} status pages.", $check);
            return;
        }

        // Gate custom domains to plans with the custom_domains feature
        $data = $this->request->getData();
        if (!empty($data['custom_domain'])) {
            $planService = new PlanService();
            if (!$planService->canUseFeature($this->currentOrgId, 'custom_domains')) {
                $this->planLimitError('Custom domains require a Pro plan or higher.', $planService->checkFeature($this->currentOrgId, 'custom_domains'));
                return;
            }
        }

        $table = $this->fetchTable('StatusPages');
        $page = $table->newEntity($data);
        $page->set('organization_id', $this->currentOrgId);

        if (!$table->save($page)) {
            $this->error('Validation failed', 422, $page->getErrors());

            return;
        }

        $this->success(['status_page' => $page], 201);
    }

    /**
     * PUT /api/v2/status-pages/{id}
     *
     * @param string $id Status page ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('StatusPages');
        $page = $table->find()
            ->where([
                'StatusPages.id' => $id,
                'StatusPages.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$page) {
            $this->error('Status page not found', 404);

            return;
        }

        // Gate custom domains to plans with the custom_domains feature
        $data = $this->request->getData();
        if (!empty($data['custom_domain'])) {
            $planService = new PlanService();
            if (!$planService->canUseFeature($this->currentOrgId, 'custom_domains')) {
                $this->planLimitError('Custom domains require a Pro plan or higher.', $planService->checkFeature($this->currentOrgId, 'custom_domains'));
                return;
            }
        }

        $page = $table->patchEntity($page, $data);
        if (!$table->save($page)) {
            $this->error('Validation failed', 422, $page->getErrors());

            return;
        }

        $this->success(['status_page' => $page]);
    }

    /**
     * DELETE /api/v2/status-pages/{id}
     *
     * @param string $id Status page ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('StatusPages');
        $page = $table->find()
            ->where([
                'StatusPages.id' => $id,
                'StatusPages.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$page) {
            $this->error('Status page not found', 404);

            return;
        }

        if (!$table->delete($page)) {
            $this->error('Failed to delete status page', 500);

            return;
        }

        $this->success(['message' => 'Status page deleted']);
    }
}
