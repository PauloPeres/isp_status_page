<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Integrations Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class IntegrationsController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/integrations');
    }

    public function view($id = null)
    {
        return $this->redirect('/app/integrations/' . $id);
    }

    public function add()
    {
        return $this->redirect('/app/integrations/new');
    }

    public function edit($id = null)
    {
        return $this->redirect('/app/integrations/' . $id . '/edit');
    }

    public function delete($id = null)
    {
        return $this->redirect('/app/integrations');
    }

    public function test($id = null)
    {
        return $this->redirect('/app/integrations/' . $id);
    }
}
