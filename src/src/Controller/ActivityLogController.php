<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ActivityLog Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class ActivityLogController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/activity-log');
    }
}
