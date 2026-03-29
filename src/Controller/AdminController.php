<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Admin Controller
 *
 * Redirects to Angular SPA dashboard.
 */
class AdminController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/dashboard');
    }
}
