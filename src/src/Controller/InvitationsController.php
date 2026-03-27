<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\InvitationService;
use App\Service\PermissionService;

/**
 * Invitations Controller
 *
 * Manages team invitations: listing, sending, accepting, and revoking.
 *
 * @property \App\Model\Table\InvitationsTable $Invitations
 */
class InvitationsController extends AppController
{
    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // The accept action is public (token-based auth)
        $this->Authentication->addUnauthenticatedActions(['accept']);
    }

    /**
     * List pending and recent invitations for the current organization.
     *
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission(PermissionService::ACTION_MANAGE_TEAM);

        $orgId = (int)$this->currentOrganization['id'];

        $invitations = $this->Invitations->find()
            ->where(['Invitations.organization_id' => $orgId])
            ->contain(['Inviter'])
            ->orderBy(['Invitations.created' => 'DESC'])
            ->all();

        $this->set(compact('invitations'));
    }

    /**
     * Send a team invitation.
     *
     * POST: requires email and role fields.
     *
     * @return \Cake\Http\Response|null|void Redirects to index.
     */
    public function send()
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission(PermissionService::ACTION_MANAGE_TEAM);

        $orgId = (int)$this->currentOrganization['id'];
        $identity = $this->request->getAttribute('identity');
        $userId = (int)$identity->getIdentifier();

        $email = $this->request->getData('email');
        $role = $this->request->getData('role', 'member');

        try {
            $invitationService = new InvitationService();
            $invitation = $invitationService->send($orgId, $email, $role, $userId);

            $this->Flash->success(__('Invitation sent to {0}.', $email));
        } catch (\RuntimeException $e) {
            $this->Flash->error($e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Accept an invitation via token.
     *
     * This is a public action - no authentication required.
     * The token serves as authentication.
     *
     * @param string|null $token The invitation token.
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function accept(?string $token = null)
    {
        if (!$token) {
            $this->Flash->error(__('Invalid invitation link.'));

            return $this->redirect('/');
        }

        $invitationService = new InvitationService();

        // Look up the invitation for display
        $invitation = $this->Invitations->find('byToken', token: $token)
            ->contain(['Organizations'])
            ->first();

        if (!$invitation) {
            $this->Flash->error(__('This invitation was not found.'));

            return $this->redirect('/');
        }

        if ($invitation->isAccepted()) {
            $this->Flash->success(__('This invitation has already been accepted.'));

            return $this->redirect('/users/login');
        }

        if ($invitation->isExpired()) {
            $this->Flash->error(__('This invitation has expired. Please ask for a new one.'));

            return $this->redirect('/');
        }

        // On GET, show acceptance page; on POST, actually accept
        if ($this->request->is('post')) {
            $accepted = $invitationService->accept($token);

            if ($accepted) {
                $this->Flash->success(__('Welcome! You have joined {0}.', $invitation->organization->name));

                return $this->redirect('/users/login');
            }

            $this->Flash->error(__('Failed to accept the invitation. Please try again.'));
        }

        $this->set(compact('invitation'));
    }

    /**
     * Revoke (cancel) a pending invitation.
     *
     * @param int|null $id The invitation ID.
     * @return \Cake\Http\Response|null Redirects to index.
     */
    public function revoke(?int $id = null)
    {
        $this->request->allowMethod(['post']);
        $this->checkPermission(PermissionService::ACTION_MANAGE_TEAM);

        $invitationService = new InvitationService();
        $revoked = $invitationService->revoke((int)$id);

        if ($revoked) {
            $this->Flash->success(__('Invitation revoked successfully.'));
        } else {
            $this->Flash->error(__('Could not revoke the invitation.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
