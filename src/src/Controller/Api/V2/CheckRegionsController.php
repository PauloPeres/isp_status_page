<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * CheckRegionsController
 *
 * Manage check regions for multi-region monitoring.
 */
class CheckRegionsController extends AppController
{
    /**
     * GET /api/v2/check-regions
     *
     * List all check regions (active by default).
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('CheckRegions');
        $query = $table->find();

        $showAll = $this->request->getQuery('all');
        if (!$showAll) {
            $query->where(['CheckRegions.active' => true]);
        }

        $regions = $query->orderBy(['CheckRegions.name' => 'ASC'])->all()->toArray();

        $this->success([
            'items' => $regions,
            'pagination' => [
                'page' => 1,
                'limit' => count($regions),
                'total' => count($regions),
                'pages' => 1,
            ],
        ]);
    }

    /**
     * GET /api/v2/check-regions/:id
     *
     * @param string $id Region ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('CheckRegions');

        try {
            $region = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Region not found', 404);
            return;
        }

        $this->success(['region' => $region]);
    }

    /**
     * POST /api/v2/check-regions
     *
     * Create a new check region (super admin only).
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('CheckRegions');
        $region = $table->newEntity($this->request->getData());

        if (!$table->save($region)) {
            $this->error('Validation failed', 422, $region->getErrors());
            return;
        }

        $this->success(['region' => $region], 201);
    }

    /**
     * PUT /api/v2/check-regions/:id
     *
     * Update a check region (super admin only).
     *
     * @param string $id Region ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('CheckRegions');

        try {
            $region = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Region not found', 404);
            return;
        }

        $region = $table->patchEntity($region, $this->request->getData());

        if (!$table->save($region)) {
            $this->error('Validation failed', 422, $region->getErrors());
            return;
        }

        $this->success(['region' => $region]);
    }

    /**
     * DELETE /api/v2/check-regions/:id
     *
     * Delete a check region (super admin only).
     *
     * @param string $id Region ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $table = $this->fetchTable('CheckRegions');

        try {
            $region = $table->get((int)$id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error('Region not found', 404);
            return;
        }

        if ($table->delete($region)) {
            $this->success(['message' => 'Region deleted']);
        } else {
            $this->error('Failed to delete region', 500);
        }
    }
}
