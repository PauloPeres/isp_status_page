<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

class UsersController extends AppController
{
    public function index()
    {
        $usersTable = $this->fetchTable('Users');
        $query = $usersTable->find()
            ->contain(['OrganizationUsers' => ['Organizations']]);

        // Search
        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where(['OR' => [
                'Users.username LIKE' => "%$search%",
                'Users.email LIKE' => "%$search%",
            ]]);
        }

        $query->orderBy(['Users.created' => 'DESC']);
        $users = $this->paginate($query, ['limit' => 25]);
        $this->set(compact('users', 'search'));
    }

    public function view($id = null)
    {
        $user = $this->fetchTable('Users')->get($id, contain: [
            'OrganizationUsers' => ['Organizations'],
        ]);

        // API keys for this user
        $apiKeys = $this->fetchTable('ApiKeys')->find()
            ->where(['user_id' => $id])
            ->applyOptions(['skipTenantScope' => true])
            ->orderBy(['created' => 'DESC'])
            ->all();

        $this->set(compact('user', 'apiKeys'));
    }
}
