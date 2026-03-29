<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Checks Controller - Redirects to Angular SPA.
 */
class ChecksController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/checks');
    }

    public function view($id = null)
    {
        return $this->redirect('/app/checks');
    }
}
