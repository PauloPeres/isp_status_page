<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * ScheduledReports Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class ScheduledReportsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/scheduled-reports');
    }

    public function add()
    {
        return $this->redirect('/app/scheduled-reports/new');
    }

    public function edit(?string $id = null)
    {
        return $this->redirect('/app/scheduled-reports/' . $id . '/edit');
    }

    public function delete(?string $id = null): ?Response
    {
        return $this->redirect('/app/scheduled-reports');
    }

    public function preview(?string $id = null)
    {
        return $this->redirect('/app/scheduled-reports/' . $id);
    }

    public function sendNow(?string $id = null): ?Response
    {
        return $this->redirect('/app/scheduled-reports');
    }
}
