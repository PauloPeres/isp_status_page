<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Dashboard Controller
 *
 * Redirects to the Angular SPA dashboard.
 * The legacy CakePHP admin dashboard has been replaced by Angular.
 */
class DashboardController extends AppController
{
    /**
     * Index method - Redirect to Angular dashboard
     *
     * @return \Cake\Http\Response
     */
    public function index()
    {
        return $this->redirect('/app/dashboard');
    }
}
