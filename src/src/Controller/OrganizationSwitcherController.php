<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;

/**
 * Organization Switcher Controller
 *
 * Allows users who belong to multiple organizations to switch between them.
 */
class OrganizationSwitcherController extends AppController
{
    /**
     * Show list of user's organizations for selection.
     *
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function select()
    {
        $this->viewBuilder()->setLayout('admin');

        $identity = $this->request->getAttribute('identity');
        $userId = (int)$identity->getIdentifier();

        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $userOrgs = $orgUsersTable->find()
            ->contain(['Organizations'])
            ->where(['OrganizationUsers.user_id' => $userId])
            ->all();

        $this->set('userOrgs', $userOrgs);
        $this->set('currentOrgId', $this->currentOrganization['id'] ?? null);
    }

    /**
     * Switch to a different organization.
     *
     * Updates the session to set the new current organization.
     *
     * @param int|null $orgId The organization ID to switch to.
     * @return \Cake\Http\Response|null Redirects to dashboard.
     */
    public function switch(?int $orgId = null)
    {
        $this->request->allowMethod(['post']);

        if (!$orgId) {
            $this->Flash->error(__('Invalid organization.'));

            return $this->redirect(['action' => 'select']);
        }

        $identity = $this->request->getAttribute('identity');
        $userId = (int)$identity->getIdentifier();

        // Verify user belongs to this organization
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $membership = $orgUsersTable->find()
            ->contain(['Organizations'])
            ->where([
                'OrganizationUsers.user_id' => $userId,
                'OrganizationUsers.organization_id' => $orgId,
            ])
            ->first();

        if (!$membership || !$membership->organization) {
            $this->Flash->error(__('You do not have access to this organization.'));

            return $this->redirect(['action' => 'select']);
        }

        // Check if the organization is active
        if (!$membership->organization->active) {
            $this->Flash->error(__('This organization is not active.'));

            return $this->redirect(['action' => 'select']);
        }

        // Update session with new organization
        $session = $this->request->getSession();
        $session->write('current_organization_id', $orgId);

        Log::info('User switched organization', [
            'user_id' => $userId,
            'organization_id' => $orgId,
            'organization_name' => $membership->organization->name,
        ]);

        $this->Flash->success(__('Switched to {0}.', $membership->organization->name));

        return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
    }
}
