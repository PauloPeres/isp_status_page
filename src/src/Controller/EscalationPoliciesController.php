<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * EscalationPolicies Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class EscalationPoliciesController extends AppController
{
    public function index()
    {
        return $this->redirect('/app/escalation-policies');
    }

    public function view(?string $id = null)
    {
        return $this->redirect('/app/escalation-policies/' . $id);
    }

    public function add()
    {
        return $this->redirect('/app/escalation-policies/new');
    }

    public function edit(?string $id = null)
    {
        return $this->redirect('/app/escalation-policies/' . $id . '/edit');
    }

    public function delete(?string $id = null)
    {
        return $this->redirect('/app/escalation-policies');
    }
}
