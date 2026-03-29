<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * ScheduledReportsController (TASK-NG-011)
 *
 * CRUD for scheduled reports plus preview and send-now actions.
 */
class ScheduledReportsController extends AppController
{
    /**
     * GET /api/v2/scheduled-reports
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('ScheduledReports');
        $reports = $table->find()
            ->where(['ScheduledReports.organization_id' => $this->currentOrgId])
            ->orderBy(['ScheduledReports.name' => 'ASC'])
            ->all();

        $this->success(['scheduled_reports' => $reports->toArray()]);
    }

    /**
     * GET /api/v2/scheduled-reports/{id}
     *
     * @param string $id Scheduled report ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('ScheduledReports');
        $report = $table->find()
            ->where([
                'ScheduledReports.id' => $id,
                'ScheduledReports.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$report) {
            $this->error('Scheduled report not found', 404);

            return;
        }

        $this->success(['scheduled_report' => $report]);
    }

    /**
     * POST /api/v2/scheduled-reports
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('ScheduledReports');
        $report = $table->newEntity($this->request->getData());
        $report->set('organization_id', $this->currentOrgId);

        if (!$table->save($report)) {
            $this->error('Validation failed', 422, $report->getErrors());

            return;
        }

        $this->success(['scheduled_report' => $report], 201);
    }

    /**
     * PUT /api/v2/scheduled-reports/{id}
     *
     * @param string $id Scheduled report ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('ScheduledReports');
        $report = $table->find()
            ->where([
                'ScheduledReports.id' => $id,
                'ScheduledReports.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$report) {
            $this->error('Scheduled report not found', 404);

            return;
        }

        $report = $table->patchEntity($report, $this->request->getData());
        if (!$table->save($report)) {
            $this->error('Validation failed', 422, $report->getErrors());

            return;
        }

        $this->success(['scheduled_report' => $report]);
    }

    /**
     * DELETE /api/v2/scheduled-reports/{id}
     *
     * @param string $id Scheduled report ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('ScheduledReports');
        $report = $table->find()
            ->where([
                'ScheduledReports.id' => $id,
                'ScheduledReports.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$report) {
            $this->error('Scheduled report not found', 404);

            return;
        }

        if (!$table->delete($report)) {
            $this->error('Failed to delete scheduled report', 500);

            return;
        }

        $this->success(['message' => 'Scheduled report deleted']);
    }

    /**
     * GET /api/v2/scheduled-reports/{id}/preview
     *
     * Preview a scheduled report without sending it.
     *
     * @param string $id Scheduled report ID.
     * @return void
     */
    public function preview(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('ScheduledReports');
        $report = $table->find()
            ->where([
                'ScheduledReports.id' => $id,
                'ScheduledReports.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$report) {
            $this->error('Scheduled report not found', 404);

            return;
        }

        try {
            $service = new \App\Service\ScheduledReportService();
            $preview = $service->preview($report);

            $this->success(['preview' => $preview]);
        } catch (\Exception $e) {
            $this->error('Failed to generate preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/scheduled-reports/{id}/send-now
     *
     * Immediately send a scheduled report.
     *
     * @param string $id Scheduled report ID.
     * @return void
     */
    public function sendNow(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('ScheduledReports');
        $report = $table->find()
            ->where([
                'ScheduledReports.id' => $id,
                'ScheduledReports.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$report) {
            $this->error('Scheduled report not found', 404);

            return;
        }

        try {
            $service = new \App\Service\ScheduledReportService();
            $service->sendNow($report);

            $this->success(['message' => 'Report sent successfully']);
        } catch (\Exception $e) {
            $this->error('Failed to send report: ' . $e->getMessage(), 500);
        }
    }
}
