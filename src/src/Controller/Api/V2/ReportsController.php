<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\ReportService;

/**
 * ReportsController
 *
 * Generate CSV reports for uptime, incidents, and response times.
 */
class ReportsController extends AppController
{
    /**
     * Extract and validate date params — accepts both from/to and start/end.
     */
    private function getDates(): array
    {
        $from = $this->request->getQuery('from') ?? $this->request->getQuery('start');
        $to = $this->request->getQuery('to') ?? $this->request->getQuery('end');

        // Validate date format to prevent error message leaking internals
        if ($from !== null) {
            try {
                new \DateTime($from);
            } catch (\Exception $e) {
                $from = null;
            }
        }
        if ($to !== null) {
            try {
                new \DateTime($to);
            } catch (\Exception $e) {
                $to = null;
            }
        }

        return [$from, $to];
    }

    /**
     * Send CSV response with proper headers and UTF-8 BOM.
     */
    private function sendCsv(string $csv, string $filename): void
    {
        $this->autoRender = false;
        $this->response = $this->response
            ->withType('text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->withStringBody($csv);
    }

    /**
     * GET /api/v2/reports/uptime
     */
    public function uptime(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new ReportService();
            [$from, $to] = $this->getDates();
            $csv = $service->generateUptimeCsv($this->currentOrgId, $from, $to);
            $this->sendCsv($csv, 'uptime-report.csv');
        } catch (\Throwable $e) {
            $this->log('Report generation failed: ' . $e->getMessage(), 'error');
            $this->error('Failed to generate report. Please check your date parameters and try again.', 500);
        }
    }

    /**
     * GET /api/v2/reports/incidents
     */
    public function incidents(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new ReportService();
            [$from, $to] = $this->getDates();
            $csv = $service->generateIncidentsCsv($this->currentOrgId, $from, $to);
            $this->sendCsv($csv, 'incidents-report.csv');
        } catch (\Exception $e) {
            $this->log('Incidents report generation failed: ' . $e->getMessage(), 'error');
            $this->error('Failed to generate report. Please check your date parameters and try again.', 500);
        }
    }

    /**
     * GET /api/v2/reports/response-times
     */
    public function responseTimes(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new ReportService();
            [$from, $to] = $this->getDates();
            $csv = $service->generateResponseTimesCsv($this->currentOrgId, $from, $to);
            $this->sendCsv($csv, 'response-times-report.csv');
        } catch (\Exception $e) {
            $this->log('Response times report generation failed: ' . $e->getMessage(), 'error');
            $this->error('Failed to generate report. Please check your date parameters and try again.', 500);
        }
    }
}
