<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

/**
 * Super Admin UsersController (TASK-NG-014)
 *
 * Platform-wide user listing and details.
 */
class UsersController extends AppController
{
    /**
     * GET /api/v2/super-admin/users
     *
     * List all users across the platform.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('Users');
        $query = $table->find()
            ->orderBy(['Users.created' => 'DESC']);

        $limit = (int)($this->request->getQuery('limit') ?: 50);
        $limit = min($limit, 200);
        $page = (int)($this->request->getQuery('page') ?: 1);

        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $query->where([
                'OR' => [
                    'Users.email LIKE' => "%{$search}%",
                    'Users.username LIKE' => "%{$search}%",
                ],
            ]);
        }

        $users = $query
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->all();

        $this->success([
            'users' => $users->toArray(),
            'pagination' => ['page' => $page, 'limit' => $limit],
        ]);
    }

    /**
     * GET /api/v2/super-admin/users/{id}
     *
     * View a single user with their organization memberships.
     *
     * @param string $id User ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $user = $this->fetchTable('Users')->find()
            ->where(['Users.id' => $id])
            ->first();

        if (!$user) {
            $this->error('User not found', 404);

            return;
        }

        $orgUsers = $this->fetchTable('OrganizationUsers')->find()
            ->contain(['Organizations'])
            ->where(['OrganizationUsers.user_id' => $id])
            ->all();

        $organizations = [];
        foreach ($orgUsers as $ou) {
            $organizations[] = [
                'id' => $ou->organization_id,
                'name' => $ou->organization->name ?? '',
                'role' => $ou->role,
            ];
        }

        $this->success([
            'user' => $user,
            'organizations' => $organizations,
        ]);
    }
}
