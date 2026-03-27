<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Log\Log;

/**
 * Onboarding Controller
 *
 * Guides new users through a 3-step onboarding wizard:
 * 1. Organization name and slug customization
 * 2. Create first monitor (simple HTTP monitor)
 * 3. Invite team members (optional)
 */
class OnboardingController extends AppController
{
    /**
     * Step 1: Organization name and slug customization
     *
     * Pre-filled from registration data. User can update org name and slug.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function step1()
    {
        $this->viewBuilder()->disableAutoLayout();

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $organizationsTable = $this->fetchTable('Organizations');
        $orgUsersTable = $this->fetchTable('OrganizationUsers');

        // Find the user's organization
        $orgUser = $orgUsersTable->find()
            ->where(['user_id' => $identity->getIdentifier()])
            ->first();

        if (!$orgUser) {
            $this->Flash->error(__('No organization found. Please register first.'));
            return $this->redirect(['controller' => 'Registration', 'action' => 'register']);
        }

        $organization = $organizationsTable->get($orgUser->organization_id);

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $organization = $organizationsTable->patchEntity($organization, [
                'name' => $data['name'] ?? $organization->name,
                'slug' => $data['slug'] ?? $organization->slug,
            ]);

            if ($organizationsTable->save($organization)) {
                return $this->redirect(['action' => 'step2']);
            }

            $this->Flash->error(__('Could not save organization details. Please check the errors below.'));
        }

        $this->set(compact('organization'));
    }

    /**
     * Step 2: Create first monitor (simple HTTP monitor form)
     *
     * @return \Cake\Http\Response|null|void
     */
    public function step2()
    {
        $this->viewBuilder()->disableAutoLayout();

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $monitorsTable = $this->fetchTable('Monitors');
        $orgUsersTable = $this->fetchTable('OrganizationUsers');

        $orgUser = $orgUsersTable->find()
            ->where(['user_id' => $identity->getIdentifier()])
            ->first();

        if (!$orgUser) {
            return $this->redirect(['action' => 'step1']);
        }

        $monitor = $monitorsTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $monitorData = [
                'organization_id' => $orgUser->organization_id,
                'name' => $data['name'] ?? '',
                'type' => 'http',
                'configuration' => json_encode(['url' => $data['url'] ?? '']),
                'check_interval' => (int)($data['check_interval'] ?? 300),
                'timeout' => 30,
                'retry_count' => 3,
                'status' => 'unknown',
                'active' => true,
                'visible_on_status_page' => true,
                'display_order' => 0,
            ];

            $monitor = $monitorsTable->patchEntity($monitor, $monitorData);

            if ($monitorsTable->save($monitor)) {
                return $this->redirect(['action' => 'step3']);
            }

            $this->Flash->error(__('Could not create monitor. Please check the errors below.'));
        }

        $this->set(compact('monitor'));
    }

    /**
     * Step 3: Invite team members (email + role, optional skip)
     *
     * @return \Cake\Http\Response|null|void
     */
    public function step3()
    {
        $this->viewBuilder()->disableAutoLayout();

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Process invitations if provided
            $emails = $data['emails'] ?? [];
            $roles = $data['roles'] ?? [];

            if (!empty($emails)) {
                $invitationsTable = $this->fetchTable('Invitations');
                $orgUsersTable = $this->fetchTable('OrganizationUsers');

                $orgUser = $orgUsersTable->find()
                    ->where(['user_id' => $identity->getIdentifier()])
                    ->first();

                if ($orgUser) {
                    $invitedCount = 0;
                    foreach ($emails as $index => $email) {
                        $email = trim($email);
                        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }

                        $role = $roles[$index] ?? 'member';
                        if (!in_array($role, ['admin', 'member', 'viewer'], true)) {
                            $role = 'member';
                        }

                        try {
                            $invitation = $invitationsTable->newEntity([
                                'organization_id' => $orgUser->organization_id,
                                'email' => $email,
                                'role' => $role,
                                'token' => bin2hex(random_bytes(32)),
                                'invited_by' => (int)$identity->getIdentifier(),
                                'expires_at' => new \DateTime('+7 days'),
                            ]);

                            if ($invitationsTable->save($invitation)) {
                                $invitedCount++;
                            }
                        } catch (\Exception $e) {
                            Log::error("Failed to create invitation for {$email}: " . $e->getMessage());
                        }
                    }

                    if ($invitedCount > 0) {
                        $this->Flash->success(__('Invited {0} team member(s).', $invitedCount));
                    }
                }
            }

            return $this->redirect(['action' => 'complete']);
        }
    }

    /**
     * Complete: "All set!" page, redirect to dashboard
     *
     * @return \Cake\Http\Response|null|void
     */
    public function complete()
    {
        $this->viewBuilder()->disableAutoLayout();

        $identity = $this->request->getAttribute('identity');
        if (!$identity) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }
}
