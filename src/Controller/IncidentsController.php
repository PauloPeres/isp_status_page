<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Incidents Controller - Redirects to Angular SPA.
 */
class IncidentsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/incidents');
    }

    public function view($id = null)
    {
        return $this->redirect('/app/incidents/' . $id);
    }
}
