<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Admin Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class AdminController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/dashboard');
    }
}
