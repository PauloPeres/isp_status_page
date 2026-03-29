<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * SlaController (TASK-NG-008)
 *
 * CRUD for SLA definitions plus report and CSV export.
 */
class SlaController extends AppController
{
    /**
     * GET /api/v2/sla
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('SlaDefinitions');
        $slas = $table->find()
            ->where(['SlaDefinitions.organization_id' => $this->currentOrgId])
            ->orderBy(['SlaDefinitions.name' => 'ASC'])
            ->all();

        $this->success(['slas' => $slas->toArray()]);
    }

    /**
     * GET /api/v2/sla/{id}
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $table->find()
            ->where([
                'SlaDefinitions.id' => $id,
                'SlaDefinitions.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        $this->success(['sla' => $sla]);
    }

    /**
     * POST /api/v2/sla
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $table->newEntity($this->request->getData());
        $sla->set('organization_id', $this->currentOrgId);

        if (!$table->save($sla)) {
            $this->error('Validation failed', 422, $sla->getErrors());

            return;
        }

        $this->success(['sla' => $sla], 201);
    }

    /**
     * PUT /api/v2/sla/{id}
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $table->find()
            ->where([
                'SlaDefinitions.id' => $id,
                'SlaDefinitions.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        $sla = $table->patchEntity($sla, $this->request->getData());
        if (!$table->save($sla)) {
            $this->error('Validation failed', 422, $sla->getErrors());

            return;
        }

        $this->success(['sla' => $sla]);
    }

    /**
     * DELETE /api/v2/sla/{id}
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $table->find()
            ->where([
                'SlaDefinitions.id' => $id,
                'SlaDefinitions.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        if (!$table->delete($sla)) {
            $this->error('Failed to delete SLA', 500);

            return;
        }

        $this->success(['message' => 'SLA deleted']);
    }

    /**
     * GET /api/v2/sla/{id}/report
     *
     * Return SLA compliance report data for the given SLA.
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function report(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $table->find()
            ->where([
                'SlaDefinitions.id' => $id,
                'SlaDefinitions.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        try {
            $service = new \App\Service\SlaService();
            $reportData = $service->generateReport($sla);

            $this->success(['report' => $reportData]);
        } catch (\Exception $e) {
            $this->error('Failed to generate report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/sla/{id}/export
     *
     * Export SLA report as CSV.
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function export(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $table->find()
            ->where([
                'SlaDefinitions.id' => $id,
                'SlaDefinitions.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        try {
            $service = new \App\Service\SlaService();
            $csv = $service->exportReportCsv($sla);

            $this->response = $this->response
                ->withType('text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="sla-report-' . $id . '.csv"')
                ->withStringBody($csv);
        } catch (\Exception $e) {
            $this->error('Failed to export report: ' . $e->getMessage(), 500);
        }
    }
}
