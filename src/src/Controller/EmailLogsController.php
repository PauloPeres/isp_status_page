<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * EmailLogs Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class EmailLogsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/email-logs');
    }

    public function view($id = null)
    {
        return $this->redirect('/app/email-logs');
    }

    public function resend($id = null)
    {
        return $this->redirect('/app/email-logs');
    }
}
