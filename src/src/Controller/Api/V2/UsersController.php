<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * UsersController (TASK-NG-010)
 *
 * Team user management within the current organization.
 */
class UsersController extends AppController
{
    /**
     * GET /api/v2/users
     *
     * List all users in the current organization.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $orgUsers = $this->fetchTable('OrganizationUsers')->find()
            ->contain(['Users'])
            ->where(['OrganizationUsers.organization_id' => $this->currentOrgId])
            ->all();

        $users = [];
        foreach ($orgUsers as $ou) {
            $users[] = [
                'id' => $ou->user_id,
                'username' => $ou->user->username ?? '',
                'email' => $ou->user->email ?? '',
                'role' => $ou->role,
                'joined_at' => $ou->created ? $ou->created->toIso8601String() : null,
            ];
        }

        $this->success(['users' => $users]);
    }

    /**
     * GET /api/v2/users/{id}
     *
     * View a single user within the organization.
     *
     * @param string $id User ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->contain(['Users'])
            ->where([
                'OrganizationUsers.user_id' => $id,
                'OrganizationUsers.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$orgUser) {
            $this->error('User not found in this organization', 404);

            return;
        }

        $this->success([
            'user' => [
                'id' => $orgUser->user_id,
                'username' => $orgUser->user->username ?? '',
                'email' => $orgUser->user->email ?? '',
                'role' => $orgUser->role,
                'joined_at' => $orgUser->created ? $orgUser->created->toIso8601String() : null,
            ],
        ]);
    }

    /**
     * PUT /api/v2/users/{id}/role
     *
     * Update a user's role within the organization.
     *
     * @param string $id User ID.
     * @return void
     */
    public function updateRole(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $role = $this->request->getData('role');
        $validRoles = ['admin', 'member', 'viewer'];
        if (!in_array($role, $validRoles, true)) {
            $this->error('Invalid role. Must be one of: ' . implode(', ', $validRoles), 400);

            return;
        }

        $table = $this->fetchTable('OrganizationUsers');
        $orgUser = $table->find()
            ->where([
                'OrganizationUsers.user_id' => $id,
                'OrganizationUsers.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$orgUser) {
            $this->error('User not found in this organization', 404);

            return;
        }

        if ($orgUser->role === 'owner') {
            $this->error('Cannot change the owner role', 403);

            return;
        }

        $orgUser->set('role', $role);
        if (!$table->save($orgUser)) {
            $this->error('Failed to update role', 500);

            return;
        }

        $this->success(['message' => 'Role updated', 'role' => $role]);
    }

    /**
     * DELETE /api/v2/users/{id}
     *
     * Remove a user from the organization.
     *
     * @param string $id User ID.
     * @return void
     */
    public function remove(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('OrganizationUsers');
        $orgUser = $table->find()
            ->where([
                'OrganizationUsers.user_id' => $id,
                'OrganizationUsers.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$orgUser) {
            $this->error('User not found in this organization', 404);

            return;
        }

        if ($orgUser->role === 'owner') {
            $this->error('Cannot remove the organization owner', 403);

            return;
        }

        if ((int)$id === $this->currentUserId) {
            $this->error('Cannot remove yourself', 403);

            return;
        }

        if (!$table->delete($orgUser)) {
            $this->error('Failed to remove user', 500);

            return;
        }

        $this->success(['message' => 'User removed from organization']);
    }
}
