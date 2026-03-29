<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * ReportsController (TASK-NG-011)
 *
 * Generate CSV reports for uptime, incidents, and response times.
 */
class ReportsController extends AppController
{
    /**
     * GET /api/v2/reports/uptime
     *
     * Export uptime report as CSV.
     *
     * @return void
     */
    public function uptime(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new \App\Service\ReportService();
            $csv = $service->generateUptimeCsv(
                $this->currentOrgId,
                $this->request->getQuery('from'),
                $this->request->getQuery('to')
            );

            $this->response = $this->response
                ->withType('text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="uptime-report.csv"')
                ->withStringBody($csv);
        } catch (\Exception $e) {
            $this->error('Failed to generate uptime report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/reports/incidents
     *
     * Export incidents report as CSV.
     *
     * @return void
     */
    public function incidents(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new \App\Service\ReportService();
            $csv = $service->generateIncidentsCsv(
                $this->currentOrgId,
                $this->request->getQuery('from'),
                $this->request->getQuery('to')
            );

            $this->response = $this->response
                ->withType('text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="incidents-report.csv"')
                ->withStringBody($csv);
        } catch (\Exception $e) {
            $this->error('Failed to generate incidents report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/reports/response-times
     *
     * Export response times report as CSV.
     *
     * @return void
     */
    public function responseTimes(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new \App\Service\ReportService();
            $csv = $service->generateResponseTimesCsv(
                $this->currentOrgId,
                $this->request->getQuery('from'),
                $this->request->getQuery('to')
            );

            $this->response = $this->response
                ->withType('text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="response-times-report.csv"')
                ->withStringBody($csv);
        } catch (\Exception $e) {
            $this->error('Failed to generate response times report: ' . $e->getMessage(), 500);
        }
    }
}
