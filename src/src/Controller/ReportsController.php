<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Reports Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class ReportsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/reports');
    }

    public function uptimeReport()
    {
        return $this->redirect('/app/reports/uptime');
    }

    public function incidentReport()
    {
        return $this->redirect('/app/reports/incidents');
    }

    public function responseTimeReport()
    {
        return $this->redirect('/app/reports/response-time');
    }
}
