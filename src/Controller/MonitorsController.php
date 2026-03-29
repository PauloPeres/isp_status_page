<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Monitors Controller - Redirects to Angular SPA.
 */
class MonitorsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/monitors');
    }

    public function view($id = null)
    {
        return $this->redirect('/app/monitors/' . $id);
    }

    public function add()
    {
        return $this->redirect('/app/monitors/new');
    }

    public function edit($id = null)
    {
        return $this->redirect('/app/monitors/' . $id . '/edit');
    }

    public function delete($id = null)
    {
        return $this->redirect('/app/monitors');
    }
}
