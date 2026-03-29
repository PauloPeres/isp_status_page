<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * ApiKeys Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class ApiKeysController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/api-keys');
    }

    public function add()
    {
        return $this->redirect('/app/api-keys/new');
    }

    public function delete($id = null)
    {
        return $this->redirect('/app/api-keys');
    }
}
