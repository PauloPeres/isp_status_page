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
    protected \App\Service\ScheduledReportService $scheduledReportService;

    public function initialize(): void
    {
        parent::initialize();
        $this->scheduledReportService = new \App\Service\ScheduledReportService();
    }

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
        $data = $this->request->getData();

        // Convert recipients array to JSON string before entity creation
        if (isset($data['recipients']) && is_array($data['recipients'])) {
            $data['recipients'] = json_encode(array_values(array_filter(array_map('trim', $data['recipients']))));
        }

        // Map report_type to include_* booleans
        if (isset($data['report_type'])) {
            $type = $data['report_type'];
            $data['include_uptime'] = in_array($type, ['uptime', 'sla'], true);
            $data['include_response_time'] = ($type === 'performance');
            $data['include_incidents'] = in_array($type, ['incidents', 'sla'], true);
            $data['include_sla'] = ($type === 'sla');
            unset($data['report_type']);
        }

        $report = $table->newEntity($data);
        $report->set('organization_id', $this->currentOrgId);

        // Calculate next_send_at based on frequency
        try {
            $service = $this->scheduledReportService;
            $report->set('next_send_at', $service->calculateNextSendAt($data['frequency'] ?? 'weekly'));
        } catch (\Exception $e) {
            // Non-critical
        }

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
        $this->request->allowMethod(['put', 'patch']);

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

        $data = $this->request->getData();
        if (isset($data['recipients']) && is_array($data['recipients'])) {
            $data['recipients'] = json_encode(array_values(array_filter(array_map('trim', $data['recipients']))));
        }
        if (isset($data['report_type'])) {
            $type = $data['report_type'];
            $data['include_uptime'] = in_array($type, ['uptime', 'sla'], true);
            $data['include_response_time'] = ($type === 'performance');
            $data['include_incidents'] = in_array($type, ['incidents', 'sla'], true);
            $data['include_sla'] = ($type === 'sla');
            unset($data['report_type']);
        }

        $report = $table->patchEntity($report, $data);
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
            $service = $this->scheduledReportService;
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
            $service = $this->scheduledReportService;
            $result = $service->sendReport($report);

            if ($result) {
                $this->success(['message' => 'Report sent successfully to ' . count($report->getRecipientsArray()) . ' recipient(s)']);
            } else {
                $this->error('Report generated but email delivery failed. Please check SMTP settings in Super Admin > Settings.', 422);
            }
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'SMTP') || str_contains($msg, 'smtp') || str_contains($msg, 'mail') || str_contains($msg, 'connect')) {
                $this->error('Email not configured. Go to Super Admin > Settings to set up SMTP before sending reports.', 422);
            } else {
                $this->error('Failed to send report: ' . $msg, 500);
            }
        }
    }
}
