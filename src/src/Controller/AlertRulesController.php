<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * AlertRules Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class AlertRulesController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/alert-rules');
    }

    public function add()
    {
        return $this->redirect('/app/alert-rules/new');
    }

    public function edit($id = null)
    {
        return $this->redirect('/app/alert-rules/' . $id . '/edit');
    }

    public function delete($id = null)
    {
        return $this->redirect('/app/alert-rules');
    }
}
