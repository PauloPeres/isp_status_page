<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Admin Controller
 *
 * Main dashboard for administrative panel
 */
class AdminController extends AppController
{
    /**
     * Index method - Admin Dashboard
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
    }
}
