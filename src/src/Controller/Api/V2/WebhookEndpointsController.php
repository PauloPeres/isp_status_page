<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\PlanService;

/**
 * WebhookEndpointsController (C-04)
 *
 * Manage webhook endpoints and view delivery history.
 */
class WebhookEndpointsController extends AppController
{
    /**
     * GET /api/v2/webhook-endpoints
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('WebhookEndpoints');
        $endpoints = $table->find()
            ->orderBy(['WebhookEndpoints.created' => 'DESC'])
            ->all()
            ->toArray();

        $this->success([
            'items' => $endpoints,
            'pagination' => [
                'page' => 1,
                'limit' => count($endpoints),
                'total' => count($endpoints),
                'pages' => 1,
            ],
        ]);
    }

    /**
     * POST /api/v2/webhook-endpoints
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $planService = new PlanService();
        $check = $planService->checkFeature($this->currentOrgId, 'webhook_alerts');
        if (!$check['allowed']) {
            $this->planLimitError("Webhook endpoints are not available on your {$check['plan_name']} plan. Upgrade to use webhooks.", $check);
            return;
        }

        $table = $this->fetchTable('WebhookEndpoints');
        $endpoint = $table->newEntity($this->request->getData());
        $endpoint->set('organization_id', $this->currentOrgId);

        // Auto-generate signing secret if not provided
        if (empty($endpoint->secret)) {
            $endpoint->set('secret', bin2hex(random_bytes(32)));
        }

        if (!$table->save($endpoint)) {
            $this->error('Validation failed', 422, $endpoint->getErrors());
            return;
        }

        $this->success(['webhook_endpoint' => $endpoint], 201);
    }

    /**
     * PUT /api/v2/webhook-endpoints/:id
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('WebhookEndpoints');

        try {
            $endpoint = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Webhook endpoint not found', 404);
            return;
        }

        $endpoint = $table->patchEntity($endpoint, $this->request->getData());

        if (!$table->save($endpoint)) {
            $this->error('Validation failed', 422, $endpoint->getErrors());
            return;
        }

        $this->success(['webhook_endpoint' => $endpoint]);
    }

    /**
     * DELETE /api/v2/webhook-endpoints/:id
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('WebhookEndpoints');

        try {
            $endpoint = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Webhook endpoint not found', 404);
            return;
        }

        if ($table->delete($endpoint)) {
            $this->success(['message' => 'Webhook endpoint deleted']);
        } else {
            $this->error('Failed to delete', 500);
        }
    }

    /**
     * POST /api/v2/webhook-endpoints/:id/test
     *
     * Send a test event to the webhook endpoint.
     */
    public function test(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('WebhookEndpoints');

        try {
            $endpoint = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Webhook endpoint not found', 404);
            return;
        }

        $service = new \App\Service\WebhookDeliveryService();

        try {
            $service->dispatch('test', [
                'message' => 'This is a test webhook from ISP Status Page',
                'timestamp' => date('c'),
            ], $this->currentOrgId);

            $this->success(['message' => 'Test event dispatched']);
        } catch (\Exception $e) {
            $this->error('Failed to send test: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/webhook-endpoints/:id/deliveries
     *
     * List delivery history for an endpoint.
     */
    public function deliveries(string $id): void
    {
        $this->request->allowMethod(['get']);

        $endpointTable = $this->fetchTable('WebhookEndpoints');

        try {
            $endpointTable->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Webhook endpoint not found', 404);
            return;
        }

        $deliveriesTable = $this->fetchTable('WebhookDeliveries');
        $page = (int)($this->request->getQuery('page') ?? 1);
        $limit = min((int)($this->request->getQuery('limit') ?? 25), 100);

        $query = $deliveriesTable->find()
            ->where(['WebhookDeliveries.webhook_endpoint_id' => (int)$id])
            ->orderBy(['WebhookDeliveries.created' => 'DESC'])
            ->limit($limit)
            ->page($page);

        $total = (clone $query)->count();
        $deliveries = $query->all()->toArray();

        $this->success([
            'items' => $deliveries,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }
}
