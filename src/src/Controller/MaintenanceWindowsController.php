<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * MaintenanceWindows Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class MaintenanceWindowsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/maintenance');
    }

    public function add()
    {
        return $this->redirect('/app/maintenance/new');
    }

    public function edit($id = null)
    {
        return $this->redirect('/app/maintenance/' . $id . '/edit');
    }

    public function delete($id = null)
    {
        return $this->redirect('/app/maintenance');
    }
}
