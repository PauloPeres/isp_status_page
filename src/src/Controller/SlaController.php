<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * SLA Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class SlaController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/sla');
    }

    public function add()
    {
        return $this->redirect('/app/sla/new');
    }

    public function edit(?string $id = null)
    {
        return $this->redirect('/app/sla/' . $id . '/edit');
    }

    public function delete(?string $id = null): ?Response
    {
        return $this->redirect('/app/sla');
    }

    public function report(?string $id = null)
    {
        return $this->redirect('/app/sla/' . $id);
    }

    public function exportReport(?string $id = null): Response
    {
        return $this->redirect('/app/sla/' . $id);
    }
}
